document.addEventListener('DOMContentLoaded', async () => {
    // Show loading state
    const profileContainer = document.getElementById('profile-container');
    profileContainer.innerHTML = '<div class="loading">Loading profile...</div>';
    
    try {
        const response = await fetch('http://localhost/AlkanSave/2_Application/controllers/ProfileController.php', {
            method: 'GET',
            credentials: 'include' // Required for session cookies
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.status === 'success') {
            // Update the profile page with actual data
            profileContainer.innerHTML = `
                <div class="profile-pic-section">
                    <div class="profile-pic-box">
                        <img id="profilePreview" src="${result.data.avatar || 'images/profile.svg'}" alt="Profile Picture" />
                        <div class="hover-overlay"></div>
                    </div>
                    <p class="subtagline">Certified AlkanSaver</p>
                </div>
                <div class="profile-info">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Last Name</label>
                            <p>${result.data.last_name || 'Not set'}</p>
                        </div>
                        <div class="info-item">
                            <label>First Name</label>
                            <p>${result.data.first_name || 'Not set'}</p>
                        </div>
                        <div class="info-item">
                            <label>Date of Birth</label>
                            <p>${result.data.dob || 'Not set'}</p>
                        </div>
                        <div class="info-item">
                            <label>Email Address</label>
                            <p>${result.data.email || 'Not set'}</p>
                        </div>
                        <div class="info-item button-cell">
                            <a href="user_edit_profile.html" class="edit-profile-btn">Edit Profile</a>
                        </div>
                    </div>
                </div>
            `;
        } else {
            throw new Error(result.message || 'Failed to load profile data');
        }
    } catch (error) {
        console.error('Profile load error:', error);
        profileContainer.innerHTML = `
            <div class="error-message">
                <p>⚠️ Error loading profile</p>
                <p>${error.message}</p>
                ${error.message.includes('Unauthorized') ? 
                    '<a href="login.html" class="retry-button">Please login</a>' : 
                    '<button onclick="location.reload()" class="retry-button">Try Again</button>'
                }
            </div>
        `;
    }
});