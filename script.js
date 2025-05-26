function showDetails(user) {
    document.getElementById('modalName').innerText = user.name;
    document.getElementById('modalEmail').innerText = user.email;
    document.getElementById('userModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
}

function editUser(user) {
    document.getElementById('editForm').style.display = 'block';
    document.getElementById('editId').value = user.id;
    document.getElementById('editName').value = user.name;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('addUserForm').style.display = 'none';
}

function cancelEdit() {
    document.getElementById('editForm').style.display = 'none';
}
