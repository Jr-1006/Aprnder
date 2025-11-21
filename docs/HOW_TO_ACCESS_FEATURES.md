# 🚀 How to Access All Features

## 📍 **Quick Navigation Guide**

---

## 🏠 **From Dashboard** (After Login)

### **New Navigation Bar** ✅
After logging in, you'll see a **navigation bar at the top** with these links:

1. **Dashboard** - Current page (game stats)
2. **Courses** - Browse and enroll in courses ⭐ NEW
3. **Tower Defense Game** - Play the game
4. **Leaderboard** - View top scores
5. **Profile** - Manage your profile ⭐ NEW
6. **Notifications** - View notifications ⭐ NEW
7. **⚡ Admin** - Admin panel (if you're admin/teacher) ⭐ NEW

---

## 👤 **For Regular Users**

### **1. Access Courses** 🎓
**URL**: `http://localhost/Websys/public/courses.php`

**From Dashboard**:
- Click **"Courses"** in the navigation bar

**Features**:
- ✅ Search courses by title/description
- ✅ Filter by enrollment status (All/My Courses/Available)
- ✅ Sort by (Newest/Oldest/Most Popular/Most Quests)
- ✅ Enroll in courses
- ✅ View course details

### **2. Access Profile** 👤
**URL**: `http://localhost/Websys/public/profile.php`

**From Dashboard**:
- Click **"Profile"** in the navigation bar

**Features**:
- ✅ View your statistics
- ✅ Edit profile information
- ✅ Change password
- ✅ View earned badges
- ✅ See progress

### **3. Access Notifications** 🔔
**URL**: `http://localhost/Websys/public/notifications.php`

**From Dashboard**:
- Click **"Notifications"** in the navigation bar
- Badge count shows unread notifications

**Features**:
- ✅ View all notifications
- ✅ Mark as read
- ✅ Filter by type
- ✅ Real-time updates

### **4. View Course Details** 📚
**From Courses Page**:
- Click **"Continue Learning"** (if enrolled)
- Or click **"Enroll Now"** to join

**Features**:
- ✅ View all quests in course
- ✅ Track progress percentage
- ✅ See completed quests
- ✅ Access quest submissions

### **5. Submit Code** 💻
**From Course Detail**:
- Click on any quest
- Write code in the editor
- Click **"Submit Code"**

**Features**:
- ✅ Code editor
- ✅ Submission history
- ✅ View feedback
- ✅ Resubmit if needed
- ✅ Auto-earn badges

---

## 👨‍💼 **For Admin/Teacher Users**

### **Access Admin Panel** ⚡
**URL**: `http://localhost/Websys/public/admin/dashboard.php`

**From Dashboard**:
- Click **"⚡ Admin"** in the navigation bar (yellow text)

**Requirements**:
- Your account role must be `admin` or `teacher`

---

## 🔧 **Admin Panel Features**

### **1. Admin Dashboard** 📊
**URL**: `admin/dashboard.php`

**Features**:
- ✅ Platform statistics (6 key metrics)
- ✅ Recent submissions table
- ✅ Recent users table
- ✅ Quick action buttons

**Navigation**:
- Dashboard
- Courses
- Quests
- Submissions
- Users

### **2. Manage Courses** 📚
**URL**: `admin/courses.php`

**From Admin Panel**:
- Click **"Courses"** in admin navigation

**Features**:
- ✅ View all courses with stats
- ✅ Create new course (click "➕ Create New Course")
- ✅ Edit existing courses (click "Edit" button)
- ✅ Delete courses (click "Delete" button)
- ✅ See quest count and enrollments

### **3. Manage Quests** 📝
**URL**: `admin/quests.php`

**From Admin Panel**:
- Click **"Quests"** in admin navigation

**Features**:
- ✅ View all quests with stats
- ✅ Create new quest (click "➕ Create New Quest")
- ✅ Edit existing quests (click "Edit" button)
- ✅ Delete quests (click "Delete" button)
- ✅ Set difficulty (Easy/Medium/Hard)
- ✅ Set max points

### **4. Review Submissions** 📋
**URL**: `admin/submissions.php`

**From Admin Panel**:
- Click **"Submissions"** in admin navigation

**Features**:
- ✅ View all submissions
- ✅ Filter by status (All/Pending/Passed/Failed)
- ✅ Review code submissions (click "Review" button)
- ✅ Provide feedback
- ✅ Award points
- ✅ Pass or fail submissions
- ✅ Auto-award badges when passed

### **5. Manage Users** 👥
**URL**: `admin/users.php`

**From Admin Panel**:
- Click **"Users"** in admin navigation

**Features**:
- ✅ View all users with stats
- ✅ Edit user roles (click "Edit" button)
- ✅ Change role (user/student/teacher/admin)
- ✅ Activate/deactivate accounts
- ✅ Delete users
- ✅ View user statistics

---

## 🎯 **Step-by-Step: First Time Setup**

### **For Admin/Teacher**

#### **Step 1: Access Admin Panel**
1. Login to your account
2. Look for **"⚡ Admin"** link in navigation (yellow text)
3. Click it to access admin dashboard

#### **Step 2: Create Your First Course**
1. In admin panel, click **"Courses"**
2. Click **"➕ Create New Course"** button
3. Fill in:
   - Course Title (e.g., "Introduction to Python")
   - Description (what students will learn)
4. Click **"➕ Create Course"**

#### **Step 3: Create Quests for the Course**
1. In admin panel, click **"Quests"**
2. Click **"➕ Create New Quest"** button
3. Fill in:
   - Select the course you just created
   - Quest Title (e.g., "Print Hello World")
   - Description (what students should do)
   - Difficulty (Easy/Medium/Hard)
   - Max Points (e.g., 10)
4. Click **"➕ Create Quest"**

#### **Step 4: Review Submissions**
1. Wait for students to submit code
2. In admin panel, click **"Submissions"**
3. Click **"Review"** on any submission
4. Review the code
5. Provide feedback
6. Select status (Passed/Failed)
7. Award points (if passed)
8. Click **"Submit Review"**
9. Student receives notification and badges (if earned)

---

### **For Students**

#### **Step 1: Browse Courses**
1. Login to your account
2. Click **"Courses"** in navigation
3. Use search or filters to find courses
4. Click **"Enroll Now"** on a course

#### **Step 2: Complete Quests**
1. After enrolling, click **"Continue Learning"**
2. View all quests in the course
3. Click on a quest to start
4. Write your code in the editor
5. Click **"Submit Code"**
6. Wait for instructor review

#### **Step 3: Earn Badges**
Badges are **automatically awarded** when you:
- Enroll in first course → 🎓 First Steps
- Submit first code → 💻 Code Warrior
- Pass first quest → ✅ Problem Solver
- Complete a course → 🏆 Course Champion
- Play the game → 🎮 Tower Defender
- Score 10,000+ → ⭐ Elite Defender
- Complete 10 quests → 👑 Quest Master
- Complete 3 courses → 📚 Dedicated Learner
- Get perfect score → 💯 Perfectionist

#### **Step 4: Check Notifications**
1. Click **"Notifications"** in navigation
2. View feedback from instructors
3. See badge unlocks
4. Mark as read

---

## 🔍 **Direct URLs for Quick Access**

### **User Pages**
```
Dashboard:      http://localhost/Websys/public/dashboard.php
Courses:        http://localhost/Websys/public/courses.php
Profile:        http://localhost/Websys/public/profile.php
Notifications:  http://localhost/Websys/public/notifications.php
Game:           http://localhost/Websys/public/game.php
Leaderboard:    http://localhost/Websys/public/leaderboard.php
```

### **Admin Pages** (Admin/Teacher Only)
```
Admin Dashboard:  http://localhost/Websys/public/admin/dashboard.php
Manage Courses:   http://localhost/Websys/public/admin/courses.php
Manage Quests:    http://localhost/Websys/public/admin/quests.php
Review Submissions: http://localhost/Websys/public/admin/submissions.php
Manage Users:     http://localhost/Websys/public/admin/users.php
```

### **Static Pages**
```
About:    http://localhost/Websys/public/about.php
Terms:    http://localhost/Websys/public/terms.php
Privacy:  http://localhost/Websys/public/privacy.php
Help:     http://localhost/Websys/public/help.php
```

---

## ❓ **Troubleshooting**

### **"I don't see the Admin link"**
**Solution**: 
1. Check your user role in database
2. Run this SQL query:
```sql
UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
```
3. Logout and login again

### **"Courses page is empty"**
**Solution**:
1. Login as admin/teacher
2. Go to Admin Panel → Courses
3. Create at least one course
4. Create quests for that course
5. Now students can see and enroll

### **"I can't see my submissions"**
**Solution**:
1. Make sure you're enrolled in the course
2. Go to Courses → Click on enrolled course
3. Click on a quest
4. Scroll down to see "Your Submissions" section

### **"Admin panel shows 404"**
**Solution**:
1. Make sure the `admin` folder exists in `public/`
2. Check file paths are correct
3. Clear browser cache
4. Try accessing directly: `http://localhost/Websys/public/admin/dashboard.php`

---

## 🎉 **Quick Test Checklist**

### **Test User Features**
- [ ] Login successfully
- [ ] See navigation bar with all links
- [ ] Click "Courses" and see course list
- [ ] Use search to find courses
- [ ] Enroll in a course
- [ ] View course details
- [ ] Click on a quest
- [ ] Submit code
- [ ] View notifications
- [ ] Check profile
- [ ] See earned badges

### **Test Admin Features** (Admin/Teacher)
- [ ] See "⚡ Admin" link in navigation
- [ ] Access admin dashboard
- [ ] See 6 statistics cards
- [ ] Create a new course
- [ ] Edit the course
- [ ] Create a new quest
- [ ] Edit the quest
- [ ] View submissions list
- [ ] Review a submission
- [ ] Provide feedback and pass/fail
- [ ] View users list
- [ ] Edit user role

---

## 📞 **Need Help?**

### **Check These Files**
- `CONNECTIVITY_CHECK.md` - Verify all connections
- `README.md` - General setup instructions
- `database.sql` - Database schema

### **Common Issues**
1. **Database not connected**: Check `src/config.php`
2. **Session issues**: Clear browser cookies
3. **Permission denied**: Check file permissions
4. **404 errors**: Verify file paths

---

## 🎊 **Summary**

### **Main Navigation (After Login)**
```
Dashboard → Courses → Game → Leaderboard → Profile → Notifications → Admin
```

### **Admin Navigation (Admin/Teacher)**
```
Dashboard → Courses → Quests → Submissions → Users
```

### **Key Features**
- ✅ Search and filter courses
- ✅ Enroll and track progress
- ✅ Submit code and get feedback
- ✅ Earn badges automatically
- ✅ Receive notifications
- ✅ Admin CRUD for courses/quests
- ✅ Review submissions
- ✅ Manage users

**Everything is accessible from the navigation bar!** 🎉
