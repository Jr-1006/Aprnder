<?php

class BadgeService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Check and award badges for a user
     */
    public function checkAndAwardBadges($userId) {
        $badges = [
            'first_enrollment' => 'Award for enrolling in first course',
            'first_submission' => 'Award for first code submission',
            'first_pass' => 'Award for first passed quest',
            'course_complete' => 'Award for completing a course',
            'game_player' => 'Award for playing the game',
            'high_scorer' => 'Award for achieving 10,000+ points in game',
            'quest_master' => 'Award for completing 10 quests',
            'dedicated_learner' => 'Award for completing 3 courses',
            'perfect_score' => 'Award for getting max points on a quest'
        ];
        
        $awardedBadges = [];
        
        // Check each badge condition
        if ($this->checkFirstEnrollment($userId)) {
            $awardedBadges[] = $this->awardBadge($userId, 'first_enrollment', 'First Steps', 'Enrolled in your first course');
        }
        
        if ($this->checkFirstSubmission($userId)) {
            $awardedBadges[] = $this->awardBadge($userId, 'first_submission', 'Code Warrior', 'Submitted your first code');
        }
        
        if ($this->checkFirstPass($userId)) {
            $awardedBadges[] = $this->awardBadge($userId, 'first_pass', 'Problem Solver', 'Passed your first quest');
        }
        
        if ($this->checkCourseComplete($userId)) {
            $awardedBadges[] = $this->awardBadge($userId, 'course_complete', 'Course Champion', 'Completed your first course');
        }
        
        if ($this->checkGamePlayer($userId)) {
            $awardedBadges[] = $this->awardBadge($userId, 'game_player', 'Tower Defender', 'Played the tower defense game');
        }
        
        if ($this->checkHighScorer($userId)) {
            $awardedBadges[] = $this->awardBadge($userId, 'high_scorer', 'Elite Defender', 'Scored 10,000+ in tower defense');
        }
        
        if ($this->checkQuestMaster($userId)) {
            $awardedBadges[] = $this->awardBadge($userId, 'quest_master', 'Quest Master', 'Completed 10 quests');
        }
        
        if ($this->checkDedicatedLearner($userId)) {
            $awardedBadges[] = $this->awardBadge($userId, 'dedicated_learner', 'Dedicated Learner', 'Completed 3 courses');
        }
        
        if ($this->checkPerfectScore($userId)) {
            $awardedBadges[] = $this->awardBadge($userId, 'perfect_score', 'Perfectionist', 'Achieved maximum points on a quest');
        }
        
        return array_filter($awardedBadges);
    }
    
    /**
     * Award a badge to a user
     */
    private function awardBadge($userId, $badgeKey, $name, $description) {
        try {
            // Check if badge exists by name
            $stmt = $this->db->prepare('SELECT id FROM badges WHERE name = ?');
            $stmt->execute([$name]);
            $badge = $stmt->fetch();
            
            if (!$badge) {
                // Create badge if it doesn't exist
                $insertBadge = $this->db->prepare('
                    INSERT INTO badges (name, description, icon_url) 
                    VALUES (?, ?, ?)
                ');
                $icon = $this->getBadgeIcon($badgeKey);
                $insertBadge->execute([$name, $description, $icon]);
                $badgeId = $this->db->lastInsertId();
            } else {
                $badgeId = $badge['id'];
            }
            
            // Check if user already has this badge
            $checkStmt = $this->db->prepare('
                SELECT id FROM user_badges WHERE user_id = ? AND badge_id = ?
            ');
            $checkStmt->execute([$userId, $badgeId]);
            
            if (!$checkStmt->fetch()) {
                // Award the badge
                $awardStmt = $this->db->prepare('
                    INSERT INTO user_badges (user_id, badge_id, awarded_at) 
                    VALUES (?, ?, NOW())
                ');
                $awardStmt->execute([$userId, $badgeId]);
                
                // Send notification
                $notifStmt = $this->db->prepare('
                    INSERT INTO notifications (user_id, message, type, created_at)
                    VALUES (?, ?, ?, NOW())
                ');
                $message = "🏆 Badge Unlocked: {$name}! {$description}";
                $notifStmt->execute([$userId, $message, 'success']);
                
                return [
                    'badge_id' => $badgeId,
                    'name' => $name,
                    'description' => $description
                ];
            }
        } catch (Exception $e) {
            error_log('Badge award error: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Get icon for badge
     */
    private function getBadgeIcon($badgeKey) {
        $icons = [
            'first_enrollment' => '🎓',
            'first_submission' => '💻',
            'first_pass' => '✅',
            'course_complete' => '🏆',
            'game_player' => '🎮',
            'high_scorer' => '⭐',
            'quest_master' => '👑',
            'dedicated_learner' => '📚',
            'perfect_score' => '💯'
        ];
        
        return $icons[$badgeKey] ?? '🏅';
    }
    
    // Badge condition checks
    
    private function checkFirstEnrollment($userId) {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM enrollments WHERE user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'] >= 1;
    }
    
    private function checkFirstSubmission($userId) {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM submissions WHERE user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'] >= 1;
    }
    
    private function checkFirstPass($userId) {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM submissions WHERE user_id = ? AND status = "passed"');
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'] >= 1;
    }
    
    private function checkCourseComplete($userId) {
        // Check if user has completed all quests in at least one course
        $stmt = $this->db->prepare('
            SELECT c.id
            FROM courses c
            INNER JOIN enrollments e ON e.course_id = c.id AND e.user_id = ?
            INNER JOIN quests q ON q.course_id = c.id
            LEFT JOIN submissions s ON s.quest_id = q.id AND s.user_id = ? AND s.status = "passed"
            GROUP BY c.id
            HAVING COUNT(DISTINCT q.id) = COUNT(DISTINCT s.quest_id)
            LIMIT 1
        ');
        $stmt->execute([$userId, $userId]);
        return $stmt->fetch() !== false;
    }
    
    private function checkGamePlayer($userId) {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM game_scores WHERE user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'] >= 1;
    }
    
    private function checkHighScorer($userId) {
        $stmt = $this->db->prepare('SELECT MAX(score) as max_score FROM game_scores WHERE user_id = ?');
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result && $result['max_score'] >= 10000;
    }
    
    private function checkQuestMaster($userId) {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM submissions WHERE user_id = ? AND status = "passed"');
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'] >= 10;
    }
    
    private function checkDedicatedLearner($userId) {
        // Check if user has completed 3 or more courses
        $stmt = $this->db->prepare('
            SELECT COUNT(DISTINCT c.id) as count
            FROM courses c
            INNER JOIN enrollments e ON e.course_id = c.id AND e.user_id = ?
            INNER JOIN quests q ON q.course_id = c.id
            LEFT JOIN submissions s ON s.quest_id = q.id AND s.user_id = ? AND s.status = "passed"
            GROUP BY c.id
            HAVING COUNT(DISTINCT q.id) = COUNT(DISTINCT s.quest_id)
        ');
        $stmt->execute([$userId, $userId]);
        return count($stmt->fetchAll()) >= 3;
    }
    
    private function checkPerfectScore($userId) {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as count 
            FROM submissions s
            INNER JOIN quests q ON q.id = s.quest_id
            WHERE s.user_id = ? AND s.status = "passed" AND s.points_awarded = q.max_points
        ');
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'] >= 1;
    }
}
