class ImageUploadManager {
    constructor() {
        this.token = localStorage.getItem('auth_token');
    }

    // Upload profile image
    async uploadProfileImage(imageFile) {
        const formData = new FormData();
        formData.append('image', imageFile);

        try {
            const response = await fetch('/api/upload/update-profile-image.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                },
                body: formData
            });

            const result = await response.json();
            return result;
        } catch (error) {
            return { success: false, message: 'Upload failed: ' + error.message };
        }
    }

    // Upload base64 image
    async uploadBase64Image(base64Data, folder = 'users') {
        try {
            const response = await fetch('/api/upload/image.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    image: base64Data,
                    folder: folder
                })
            });

            const result = await response.json();
            return result;
        } catch (error) {
            return { success: false, message: 'Upload failed: ' + error.message };
        }
    }

    // Create file input and handle upload
    createFileUploader(onSuccess, onError) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        
        input.onchange = async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            // Validate file
            if (!this.validateImage(file)) {
                onError && onError('Invalid image file');
                return;
            }

            // Show loading indicator
            this.showLoadingIndicator();

            try {
                const result = await this.uploadProfileImage(file);
                this.hideLoadingIndicator();

                if (result.success) {
                    onSuccess && onSuccess(result);
                } else {
                    onError && onError(result.message);
                }
            } catch (error) {
                this.hideLoadingIndicator();
                onError && onError('Upload failed: ' + error.message);
            }
        };

        return input;
    }

    // Validate image file
    validateImage(file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (file.size > maxSize) {
            alert('Image too large. Maximum size is 5MB.');
            return false;
        }

        if (!allowedTypes.includes(file.type)) {
            alert('Invalid image format. Please use JPEG, PNG, GIF, or WebP.');
            return false;
        }

        return true;
    }

    // Show loading indicator
    showLoadingIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'upload-loading';
        indicator.innerHTML = '<div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 20px; border-radius: 5px; z-index: 9999;">Uploading image...</div>';
        document.body.appendChild(indicator);
    }

    // Hide loading indicator
    hideLoadingIndicator() {
        const indicator = document.getElementById('upload-loading');
        if (indicator) {
            indicator.remove();
        }
    }

    // Convert file to base64
    fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
            reader.onerror = error => reject(error);
        });
    }
}

// Usage example
/*
const imageManager = new ImageUploadManager();

// For profile picture upload button
document.getElementById('upload-profile-btn').onclick = () => {
    const uploader = imageManager.createFileUploader(
        (result) => {
            // Success callback
            document.getElementById('profile-image').src = result.optimizedUrl;
            alert('Profile image updated successfully!');
        },
        (error) => {
            // Error callback
            alert('Upload failed: ' + error);
        }
    );
    uploader.click();
};
*/
