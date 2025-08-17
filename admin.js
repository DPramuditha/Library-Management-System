function fillUpdateForm(id, name, email, phone, address, role) {
    document.getElementById('update_user_id').value = id;
    document.getElementById('update_name').value = name;
    document.getElementById('update_email').value = email;
    document.getElementById('update_phone').value = phone;
    document.getElementById('update_address').value = address;
    document.getElementById('update_role').value = role;

    // Scroll to the form
    document.getElementById('update_user_id').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function clearUpdateForm() {
    document.getElementById('update_user_id').value = '';
    document.getElementById('update_name').value = '';
    document.getElementById('update_email').value = '';
    document.getElementById('update_phone').value = '';
    document.getElementById('update_address').value = '';
    document.getElementById('update_role').value = '';
}