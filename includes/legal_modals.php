<!-- Terms of Service Modal -->
<div id="termsModal" class="legal-modal" style="display: none;">
    <div class="legal-modal-content">
        <div class="legal-modal-header">
            <h2>📄 Terms of Service</h2>
            <button class="legal-modal-close" onclick="closeTermsModal()">&times;</button>
        </div>
        <div class="legal-modal-body">
            <p><strong>Effective Date:</strong> <?php echo date('F j, Y'); ?></p>
            
            <h3>1. Acceptance of Terms</h3>
            <p>By accessing and using Aprnder ("the Platform"), you accept and agree to be bound by the terms and provision of this agreement.</p>
            
            <h3>2. Description of Service</h3>
            <p>Aprnder provides a gamified problem-based learning platform for programming education.</p>
            
            <h3>3. User Accounts</h3>
            <p>You are responsible for maintaining the confidentiality of your account credentials and for all activities under your account.</p>
            
            <h3>4. Acceptable Use</h3>
            <p>You agree not to misuse the Platform, including attempting to access restricted areas or interfering with other users' experience.</p>
            
            <h3>5. Intellectual Property</h3>
            <p>All Platform content is owned by Aprnder or its licensors and protected by copyright laws.</p>
            
            <h3>6. Privacy</h3>
            <p>Your use is governed by our Privacy Policy.</p>
            
            <h3>7. Limitation of Liability</h3>
            <p>Aprnder shall not be liable for indirect, incidental, or consequential damages.</p>
            
            <h3>8. Contact Information</h3>
            <p><strong>Email:</strong> mjraquino2@tip.edu.ph<br>
            <strong>Address:</strong> 363 Casal St, Quiapo, Manila, 1001 Metro Manila<br>
            <strong>Data Protection Officer:</strong> Cheska Eunice Diaz</p>
        </div>
    </div>
</div>

<!-- Privacy Policy Modal -->
<div id="privacyModal" class="legal-modal" style="display: none;">
    <div class="legal-modal-content">
        <div class="legal-modal-header">
            <h2>🔒 Privacy Policy</h2>
            <button class="legal-modal-close" onclick="closePrivacyModal()">&times;</button>
        </div>
        <div class="legal-modal-body">
            <p><strong>Effective Date:</strong> <?php echo date('F j, Y'); ?></p>
            
            <h3>1. Introduction</h3>
            <p>Aprnder is committed to protecting your privacy. This Privacy Policy explains how we collect, use, and safeguard your information.</p>
            
            <h3>2. Information We Collect</h3>
            <p><strong>Personal Information:</strong> Name, email address, profile information<br>
            <strong>Usage Data:</strong> Course progress, quiz results, game scores<br>
            <strong>Technical Data:</strong> IP address, browser type, device information</p>
            
            <h3>3. How We Use Your Information</h3>
            <p>• Provide and improve educational services<br>
            • Track learning progress and performance<br>
            • Communicate updates and notifications<br>
            • Ensure platform security</p>
            
            <h3>4. Data Sharing</h3>
            <p>We do not sell your personal information. We may share data with service providers necessary for platform operation.</p>
            
            <h3>5. Data Security</h3>
            <p>We implement industry-standard security measures to protect your data.</p>
            
            <h3>6. Your Rights</h3>
            <p>You have the right to access, correct, or delete your personal information.</p>
            
            <h3>7. Cookies</h3>
            <p>We use cookies to enhance user experience and track platform usage.</p>
            
            <h3>8. Children's Privacy</h3>
            <p>Our platform is designed for students 13 and older. Parental consent required for younger users.</p>
            
            <h3>9. Contact Information</h3>
            <p><strong>Email:</strong> mjraquino2@tip.edu.ph<br>
            <strong>Address:</strong> 363 Casal St, Quiapo, Manila, 1001 Metro Manila<br>
            <strong>Data Protection Officer:</strong> Cheska Eunice Diaz</p>
        </div>
    </div>
</div>

<script>
// Legal Modal Functions
function openTermsModal() {
    document.getElementById('termsModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeTermsModal() {
    document.getElementById('termsModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function openPrivacyModal() {
    document.getElementById('privacyModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closePrivacyModal() {
    document.getElementById('privacyModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const termsModal = document.getElementById('termsModal');
    const privacyModal = document.getElementById('privacyModal');
    
    if (event.target == termsModal) {
        closeTermsModal();
    }
    if (event.target == privacyModal) {
        closePrivacyModal();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeTermsModal();
        closePrivacyModal();
    }
});
</script>
