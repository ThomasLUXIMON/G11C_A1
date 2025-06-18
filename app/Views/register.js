document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Validation simple côté client
        if (!data.nom || !data.prenom || !data.email || !data.password || !data.confirm_password) {
            alert('Veuillez remplir tous les champs.');
            return;
        }
        if (data.password !== data.confirm_password) {
            alert('Les mots de passe ne correspondent pas.');
            return;
        }

        try {
            const response = await fetch('/register', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                },
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                alert('Inscription réussie ! Redirection vers la connexion...');
                window.location.href = result.redirect || '/login.html';
            } else {
                alert(result.message || 'Erreur lors de l\'inscription');
            }
        } catch (error) {
            alert('Erreur réseau ou serveur.');
        }
    });
});
