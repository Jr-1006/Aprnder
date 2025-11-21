# Peer-to-Peer Mentor System

## Overview
The Mentor System allows students to earn mentor status through achievements, creating a peer-to-peer learning environment where successful students can help others.

## Features

### For Students
- **Track Progress**: View your progress toward becoming a mentor at `/mentor-progress.php`
- **Achievement-Based Promotion**: Earn mentor status by meeting criteria based on:
  - Completed quests
  - Game scores
  - Courses completed
  - Badges earned
  - Perfect submissions

### For Mentors
- Access to admin panel to create courses and quests
- Review and grade student submissions
- Help guide other students on their learning journey

### For Admins
- Configure mentor promotion criteria
- Manually promote deserving students
- Auto-promote eligible students
- View promotion history and statistics

## Installation

### 1. Run Database Migration
Execute the migration SQL file to create necessary tables:

```bash
mysql -u root -p pbl_gamified < db/migrations/add_mentor_system.sql
```

Or import via phpMyAdmin:
1. Open phpMyAdmin
2. Select `pbl_gamified` database
3. Go to Import tab
4. Choose `db/migrations/add_mentor_system.sql`
5. Click "Go"

### 2. Update Existing Data (Optional)
If you have existing users with role='teacher', update them to 'mentor':

```sql
UPDATE users SET role = 'mentor' WHERE role = 'teacher';
```

### 3. Set Up Auto-Promotion (Optional)
To automatically promote eligible students daily, add this to your crontab:

```bash
0 2 * * * php /path/to/Websys/cron/auto_promote_mentors.php
```

Or on Windows Task Scheduler:
- Program: `C:\xampp\php\php.exe`
- Arguments: `C:\xampp\htdocs\Websys\cron\auto_promote_mentors.php`
- Schedule: Daily at 2:00 AM

## Default Mentor Criteria

Students must meet ALL of the following to be eligible for auto-promotion:

| Criterion | Default Value | Description |
|-----------|---------------|-------------|
| Completed Quests | 15 | Number of passed quest submissions |
| Game Score | 15,000 | Best score in tower defense game |
| Courses Completed | 2 | Number of fully completed courses |
| Badges Earned | 5 | Total achievement badges |
| Perfect Submissions | 3 | Submissions with maximum points |

## Admin Management

### Access Mentor Management
Navigate to: **Admin Panel → Mentors** (`/admin/mentors.php`)

### Tabs:

#### Overview
- View recent promotions
- See promotion statistics
- Quick access to auto-promote

#### Criteria Settings
- Adjust threshold values for each criterion
- All criteria must be met for auto-promotion
- Changes apply immediately to eligibility checks

#### Eligible Students
- List of students who meet all criteria
- Manually promote individual students
- View student progress details

#### Promotion History
- Complete audit log of all promotions
- Filter by auto/manual promotion type
- View promotion dates and notes

### Manual Promotion
1. Go to **Mentors → Eligible Students**
2. Click **Promote** next to the student
3. Or promote from Users page by changing role to "Mentor"

### Auto-Promotion
Click **⚡ Auto-Promote Eligible** button to:
- Check all students against criteria
- Automatically promote eligible students
- Award mentor badges
- Send congratulatory notifications

## Student Experience

### Viewing Progress
Students can view their mentor progress at:
- Dashboard: Click "🎓 Become a Mentor" in navigation
- Direct URL: `/mentor-progress.php`

### Progress Page Shows:
- Overall completion percentage
- Visual progress circle
- Individual criterion progress bars
- Current vs. required values
- Real-time updates as they complete achievements

### Upon Eligibility
When all criteria are met:
- Green "Eligible" banner appears
- Notification sent to student
- Auto-promotion occurs within 24 hours (if cron enabled)
- Or admin can manually promote immediately

### After Promotion
- Student role changes to "Mentor"
- Access granted to admin panel
- "Mentor" badge awarded
- Congratulatory notification sent
- Name in navigation shows ⚡ Admin link

## Badges Awarded

The system includes mentor-specific badges:

| Badge | Key | Description |
|-------|-----|-------------|
| 🎓 Mentor Eligible | `mentor_eligible` | Met all requirements |
| 👨‍🏫 Mentor | `mentor_promoted` | Promoted to mentor status |
| 🤝 Helping Hand | `helping_hand` | Reviewed 10 peer submissions |
| ⭐ Community Leader | `community_leader` | Active mentor for 30 days |
| 🏆 Top Mentor | `top_mentor` | Helped 50+ students |

## Technical Details

### Database Tables

#### `mentor_criteria`
Stores promotion criteria and thresholds.

#### `mentor_promotions`
Audit log of all mentor promotions.

#### `user_badges`
Tracks awarded badges (existing table, extended).

### Key Classes

#### `MentorPromotion` (`src/MentorPromotion.php`)
- `checkEligibility($userId)` - Check if student qualifies
- `promoteToMentor($userId, $promotedBy, $notes)` - Promote student
- `autoPromoteEligibleStudents()` - Batch promote all eligible
- `getMentorStats()` - Get mentor statistics

### API Methods

```php
$mentorPromotion = new MentorPromotion(db());

// Check eligibility
$result = $mentorPromotion->checkEligibility($userId);
// Returns: ['eligible' => bool, 'criteria' => [], 'progress' => int]

// Manual promotion
$success = $mentorPromotion->promoteToMentor($userId, $adminId, 'Great work!');

// Auto-promote all eligible
$promotedIds = $mentorPromotion->autoPromoteEligibleStudents();
```

## Customization

### Adjust Criteria Values
1. Go to **Admin → Mentors → Criteria Settings**
2. Update threshold values
3. Click "Update Criteria"

### Add New Criteria
Edit `src/MentorPromotion.php` and add to:
1. `checkCriterion()` method
2. Database table `mentor_criteria`
3. Migration file for new installations

### Change Badge Icons
Update the `icon` field in the `badges` table or migration SQL.

## Troubleshooting

### Students Not Being Auto-Promoted
1. Check if cron job is set up and running
2. Check logs at `logs/mentor_promotions.log`
3. Manually run: `php cron/auto_promote_mentors.php`
4. Verify student meets ALL criteria (check at `/mentor-progress.php`)

### Criteria Not Updating
- Ensure form CSRF token is valid
- Check database connection
- Verify admin permissions

### Migration Errors
- Check MySQL version compatibility
- Ensure database `pbl_gamified` exists
- Verify user has CREATE TABLE permissions

## Benefits of Peer-to-Peer Mentorship

1. **Student Empowerment**: High-achieving students feel valued
2. **Scalability**: Reduces instructor workload
3. **Peer Learning**: Students often learn better from peers
4. **Motivation**: Clear path to advancement encourages achievement
5. **Community**: Builds stronger learning community

## Future Enhancements

Potential additions:
- Mentor performance metrics
- Peer review system
- Mentor-specific quests
- Mentorship rewards/points
- Student-mentor matching
- Mentor leaderboards
