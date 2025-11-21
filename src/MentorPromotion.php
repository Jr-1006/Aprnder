<?php

class MentorPromotion {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Check if a student is eligible for mentor promotion
     */
    public function checkEligibility($userId) {
        // Only check for students
        $userStmt = $this->db->prepare('SELECT role FROM users WHERE id = ?');
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();
        
        if (!$user || !$user['role'] || ($user['role'] !== 'student' && $user['role'] !== 'user')) {
            return [
                'eligible' => false,
                'reason' => 'Only students can be promoted to mentor'
            ];
        }
        
        // Check if already promoted
        $checkPromotion = $this->db->prepare('SELECT id FROM mentor_promotions WHERE user_id = ?');
        $checkPromotion->execute([$userId]);
        if ($checkPromotion->fetch()) {
            return [
                'eligible' => false,
                'reason' => 'Already promoted to mentor'
            ];
        }
        
        // Get active criteria
        $criteriaStmt = $this->db->query('
            SELECT criteria_key, name, threshold_value 
            FROM mentor_criteria 
            WHERE is_active = 1
        ');
        $criteria = $criteriaStmt->fetchAll();
        
        $results = [];
        $allMet = true;
        
        foreach ($criteria as $criterion) {
            $met = $this->checkCriterion($userId, $criterion['criteria_key'], $criterion['threshold_value']);
            $results[$criterion['criteria_key']] = [
                'name' => $criterion['name'],
                'threshold' => $criterion['threshold_value'],
                'current' => $met['current'],
                'met' => $met['met']
            ];
            
            if (!$met['met']) {
                $allMet = false;
            }
        }
        
        return [
            'eligible' => $allMet,
            'criteria' => $results,
            'progress' => $this->calculateProgress($results)
        ];
    }
    
    /**
     * Check a specific criterion
     */
    private function checkCriterion($userId, $criteriaKey, $threshold) {
        switch ($criteriaKey) {
            case 'min_completed_quests':
                $stmt = $this->db->prepare('
                    SELECT COUNT(*) as count 
                    FROM submissions 
                    WHERE user_id = ? AND status = "passed"
                ');
                $stmt->execute([$userId]);
                $result = $stmt->fetch();
                $current = $result['count'];
                break;
                
            case 'min_game_score':
                $stmt = $this->db->prepare('
                    SELECT MAX(score) as score 
                    FROM game_scores 
                    WHERE user_id = ?
                ');
                $stmt->execute([$userId]);
                $result = $stmt->fetch();
                $current = $result['score'] ?? 0;
                break;
                
            case 'min_courses_completed':
                $stmt = $this->db->prepare('
                    SELECT COUNT(DISTINCT e.course_id) as count
                    FROM enrollments e
                    INNER JOIN courses c ON c.id = e.course_id
                    INNER JOIN quests q ON q.course_id = c.id
                    WHERE e.user_id = ?
                    AND NOT EXISTS (
                        SELECT 1 FROM quests q2 
                        WHERE q2.course_id = c.id 
                        AND NOT EXISTS (
                            SELECT 1 FROM submissions s 
                            WHERE s.user_id = e.user_id 
                            AND s.quest_id = q2.id 
                            AND s.status = "passed"
                        )
                    )
                ');
                $stmt->execute([$userId]);
                $result = $stmt->fetch();
                $current = $result['count'];
                break;
                
            case 'min_badges_earned':
                $stmt = $this->db->prepare('
                    SELECT COUNT(*) as count 
                    FROM user_badges 
                    WHERE user_id = ?
                ');
                $stmt->execute([$userId]);
                $result = $stmt->fetch();
                $current = $result['count'];
                break;
                
            case 'min_perfect_submissions':
                $stmt = $this->db->prepare('
                    SELECT COUNT(*) as count 
                    FROM submissions s
                    INNER JOIN quests q ON q.id = s.quest_id
                    WHERE s.user_id = ? 
                    AND s.status = "passed" 
                    AND s.points_awarded = q.max_points
                ');
                $stmt->execute([$userId]);
                $result = $stmt->fetch();
                $current = $result['count'];
                break;
                
            default:
                $current = 0;
        }
        
        return [
            'current' => $current,
            'met' => $current >= $threshold
        ];
    }
    
    /**
     * Calculate overall progress percentage
     */
    private function calculateProgress($results) {
        if (empty($results)) {
            return 0;
        }
        
        $totalCriteria = count($results);
        $metCriteria = 0;
        
        foreach ($results as $result) {
            if ($result['met']) {
                $metCriteria++;
            }
        }
        
        return round(($metCriteria / $totalCriteria) * 100);
    }
    
    /**
     * Promote a student to mentor (auto or manual)
     */
    public function promoteToMentor($userId, $promotedBy = null, $notes = null) {
        try {
            $this->db->beginTransaction();
            
            // Update user role
            $updateRole = $this->db->prepare('UPDATE users SET role = "mentor" WHERE id = ?');
            $updateRole->execute([$userId]);
            
            // Record promotion
            $promotionType = $promotedBy ? 'manual' : 'auto';
            $recordPromotion = $this->db->prepare('
                INSERT INTO mentor_promotions (user_id, promoted_by, promotion_type, notes)
                VALUES (?, ?, ?, ?)
            ');
            $recordPromotion->execute([$userId, $promotedBy, $promotionType, $notes]);
            
            // Award mentor badge
            $this->awardMentorBadge($userId);
            
            // Create notification
            $this->createPromotionNotification($userId);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Mentor promotion error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Auto-check and promote eligible students
     */
    public function autoPromoteEligibleStudents() {
        // Get all students who haven't been promoted
        $studentsStmt = $this->db->query('
            SELECT u.id 
            FROM users u
            WHERE u.role IN ("student", "user")
            AND u.role IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM mentor_promotions mp WHERE mp.user_id = u.id
            )
        ');
        $students = $studentsStmt->fetchAll();
        
        $promoted = [];
        foreach ($students as $student) {
            $eligibility = $this->checkEligibility($student['id']);
            if ($eligibility['eligible']) {
                if ($this->promoteToMentor($student['id'], null, 'Auto-promoted based on achievements')) {
                    $promoted[] = $student['id'];
                }
            }
        }
        
        return $promoted;
    }
    
    /**
     * Award mentor badge
     */
    private function awardMentorBadge($userId) {
        // Check if mentor badge exists by name
        $badgeStmt = $this->db->prepare('SELECT id FROM badges WHERE name = ?');
        $badgeStmt->execute(['Mentor']);
        $badge = $badgeStmt->fetch();
        
        if ($badge) {
            // Check if user already has this badge
            $checkBadge = $this->db->prepare('
                SELECT id FROM user_badges WHERE user_id = ? AND badge_id = ?
            ');
            $checkBadge->execute([$userId, $badge['id']]);
            
            if (!$checkBadge->fetch()) {
                // Award the badge
                $awardBadge = $this->db->prepare('
                    INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)
                ');
                $awardBadge->execute([$userId, $badge['id']]);
            }
        }
    }
    
    /**
     * Create promotion notification
     */
    private function createPromotionNotification($userId) {
        $notification = $this->db->prepare('
            INSERT INTO notifications (user_id, type, message, created_at)
            VALUES (?, ?, ?, NOW())
        ');
        $notification->execute([
            $userId,
            'success',
            '🎓 Congratulations! You\'re now a Mentor! You can now access the admin panel to create courses, quests, and help other students. Keep up the great work!'
        ]);
    }
    
    /**
     * Get mentor statistics
     */
    public function getMentorStats() {
        $stats = [];
        
        // Total mentors
        $totalStmt = $this->db->query('
            SELECT COUNT(*) as count FROM users WHERE role = "mentor"
        ');
        $stats['total_mentors'] = $totalStmt->fetch()['count'];
        
        // Auto-promoted vs manual
        $autoStmt = $this->db->query('
            SELECT promotion_type, COUNT(*) as count 
            FROM mentor_promotions 
            GROUP BY promotion_type
        ');
        while ($row = $autoStmt->fetch()) {
            $stats[$row['promotion_type'] . '_promotions'] = $row['count'];
        }
        
        // Recent promotions (last 30 days)
        $recentStmt = $this->db->query('
            SELECT COUNT(*) as count 
            FROM mentor_promotions 
            WHERE promoted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ');
        $stats['recent_promotions'] = $recentStmt->fetch()['count'];
        
        return $stats;
    }
}
