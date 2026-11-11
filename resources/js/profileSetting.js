
import { API_ENDPOINTS } from "./module/config/endpoints.js";

const PROFILE_LEGACY_API_URL = API_ENDPOINTS.profileLegacy;

async function fetchProfileData() {
    const username = sessionStorage.getItem('username') || localStorage.getItem('username') || getCookie('id');
    const csrfToken = getCookie('csrf_token');

    if (!username) {
        console.error('Username is missing');
        return;
    }

    try {
        const response = await fetch(PROFILE_LEGACY_API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': getCookie('token'),
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({username, csrf_token: csrfToken})
        });

        if (!response.ok) {
            throw new Error('Failed to fetch profile data');
        }

        const data = await response.json();
        if (data.error) {
            throw new Error(data.error);
        }

        populateProfileFields(data);
        sessionStorage.setItem('profileData', JSON.stringify(data));
    } catch (error) {
        console.error('Error loading profile data:', error);
        alert('Failed to load profile data. Please try again later.');
    }
}

function populateProfileFields(data) {
    // readonly fields
    ReadonlyFields = ['Full-name', 'email', 'editFirstName', 'editLastName'];
    // editable fields
    EditableFields = [, 'editMobileNumber', 'editFaxNumber', 'editAddressLine1', 'editAddressLine2', 'editStateProvince', 'editCountry'];
    
    // populate readonly fields
    for (const field of ReadonlyFields) {
        const element = document.getElementById(field);
        if (element) element.textContent = data[field];
        if (element) element.value = data[field];
    }
    
    // populate editable fields
    for (const field of EditableFields) {
        const element = document.getElementById(field);
        if (element) element.value = data[field];
    }
}

function updateProfile(event) {
    event.preventDefault();

    const updatedData = {
        firstName: document.getElementById('editFirstName').value,
        lastName: document.getElementById('editLastName').value,
        mobileNumber: document.getElementById('editMobileNumber').value,
        faxNumber: document.getElementById('editFaxNumber').value,
        addressLine1: document.getElementById('editAddressLine1').value,
        addressLine2: document.getElementById('editAddressLine2').value,
        stateProvince: document.getElementById('editStateProvince').value,
        country: document.getElementById('editCountry').value
    };

    // Validate input fields
    if (!validateProfileData(updatedData)) {
        return;
    }

    // Send updated data to server
    fetch('/api/profile/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(updatedData)
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to update profile');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Profile updated successfully');
                loadProfileData(); // Reload profile data
                closeEditModal(); // Close the edit modal
            } else {
                alert('Failed to update profile. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error updating profile:', error);
            alert('An error occurred while updating the profile. Please try again later.');
        });
}

function validateProfileData(data) {
    
}
