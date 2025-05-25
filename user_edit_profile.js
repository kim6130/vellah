document.addEventListener('DOMContentLoaded', async () => {
    // DOM Elements
    const profilePreview = document.getElementById('profilePreview');
    const uploadButton = document.getElementById('uploadButton');
    const saveProfileBtn = document.getElementById('saveProfileBtn');
    const successModal = document.getElementById('successModal');
    const closeSuccessModal = document.getElementById('closeSuccessModal');
    let currentProfileData = {};
    let avatarFile = null;

    // Load current profile data
    try {
        const response = await fetch('http://localhost/AlkanSave/2_Application/controllers/ProfileController.php', {
            credentials: 'include'
        });
        
        if (!response.ok) throw new Error('Failed to load profile data');
        
        const result = await response.json();
        
        if (result.status === 'success') {
            currentProfileData = result.data;
            populateForm(currentProfileData);
        } else {
            throw new Error(result.message || 'Failed to load profile');
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        showError('Error loading profile data. Please try again.');
    }

    // Avatar upload handler
    uploadButton.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type and size
        const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            showError('Please select a valid image (JPEG, PNG, GIF)');
            return;
        }
        
        if (file.size > 2000000) {
            showError('Image must be smaller than 2MB');
            return;
        }
        
        avatarFile = file;
        profilePreview.src = URL.createObjectURL(file);
    });

    // Save profile handler
    saveProfileBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        await updateProfile();
    });

    // Close success modal handler
    closeSuccessModal.addEventListener('click', () => {
        successModal.style.display = 'none';
        window.location.href = 'user_profile.html';
    });

    // Populate form with current data
    function populateForm(data) {
        document.getElementById('firstName').value = data.first_name || '';
        document.getElementById('lastName').value = data.last_name || '';
        document.getElementById('email').value = data.email || '';
        document.getElementById('dob').value = data.dob || '';
        if (data.avatar) profilePreview.src = data.avatar;
    }

    // Validate form data
    function validateForm() {
        clearErrors();
        let isValid = true;

        // Required fields validation
        if (!document.getElementById('firstName').value.trim()) {
            showError('First name is required', 'firstNameError');
            isValid = false;
        }

        if (!document.getElementById('lastName').value.trim()) {
            showError('Last name is required', 'lastNameError');
            isValid = false;
        }

        const email = document.getElementById('email').value;
        if (!email) {
            showError('Email is required', 'emailError');
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('Invalid email format', 'emailError');
            isValid = false;
        }

        if (!document.getElementById('currentPassword').value) {
            showError('Current password is required', 'currentPasswordError');
            isValid = false;
        }

        // Password confirmation validation
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        if (newPassword && newPassword !== confirmPassword) {
            showError('Passwords do not match', 'confirmPasswordError');
            isValid = false;
        }

        return isValid;
    }

    // Update profile data
    async function updateProfile() {
        if (!validateForm()) return;

        try {
            saveProfileBtn.disabled = true;
            saveProfileBtn.textContent = 'Saving...';

            // Create FormData and append all fields
            const formData = new FormData();
            formData.append('first_name', document.getElementById('firstName').value.trim());
            formData.append('last_name', document.getElementById('lastName').value.trim());
            formData.append('email', document.getElementById('email').value.trim());
            formData.append('dob', document.getElementById('dob').value);
            formData.append('password', document.getElementById('currentPassword').value);
            
            // Append new password if provided
            const newPassword = document.getElementById('newPassword').value;
            if (newPassword) {
                formData.append('new_password', newPassword);
            }
            
            // Append avatar file if selected
            if (avatarFile) {
                formData.append('avatar', avatarFile, `user_avatar_${Date.now()}_${avatarFile.name}`);
                console.log('Appending avatar file:', {
                    name: avatarFile.name,
                    size: avatarFile.size,
                    type: avatarFile.type
                });
            }

            // Debug: Log FormData contents
            for (const [key, value] of formData.entries()) {
                console.log(key, value instanceof File ? 
                    `${value.name} (${value.size} bytes)` : 
                    value);
            }

            // Send request to server
            const response = await fetch('http://localhost/AlkanSave/2_Application/controllers/EditProfileController.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            // Handle response
            const responseText = await response.text();
            console.log('Raw response:', responseText);

            try {
                const result = JSON.parse(responseText);
                
                if (!response.ok || result.status !== 'success') {
                    throw new Error(result.message || 'Profile update failed');
                }
                
                // Show success and update avatar if changed
                successModal.style.display = 'flex';
                if (result.data?.avatar) {
                    profilePreview.src = result.data.avatar;
                }
                
            } catch (e) {
                if (responseText.includes('<') || responseText.includes('Warning') || responseText.includes('Error')) {
                    console.error('Server returned HTML error:', responseText);
                    throw new Error('Server error occurred. Please check console for details.');
                } else {
                    throw new Error(responseText || 'Failed to parse server response');
                }
            }
            
        } catch (error) {
            console.error('Update error:', error);
            showError(error.message);
        } finally {
            saveProfileBtn.disabled = false;
            saveProfileBtn.textContent = 'Save Profile';
        }
    }

    // Helper functions
    function showError(message, elementId = 'formError') {
        document.getElementById(elementId).textContent = message;
    }

    function clearErrors() {
        document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
    }
});