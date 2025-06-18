// Gestion du formulaire de connexion

document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    handleLogin();
});

async function handleLogin() {
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const loading = document.getElementById('loading');
    
    // Validation côté client
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    
    if (!email || !password) {
        showAlert('Veuillez remplir tous les champs.', 'error');
        return;
    }

    if (!isValidEmail(email)) {
        showAlert('Veuillez entrer une adresse email valide.', 'error');
        return;
    }

    // Désactiver le bouton et afficher le chargement
    submitBtn.disabled = true;
    submitBtn.textContent = 'Connexion...';
    loading.style.display = 'block';

    try {
        // Préparer les données du formulaire
        const formData = new FormData(form);
        
        // Envoi au serveur
        const response = await fetch('/login', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const result = await response.json();

        if (response.ok && result.success) {
            showAlert('Connexion réussie ! Redirection...', 'success');
            
            // Redirection après succès
            setTimeout(() => {
                window.location.href = result.redirect || 'dashboard.php';
            }, 1500);
        } else {
            showAlert(result.message || 'Erreur de connexion. Vérifiez vos identifiants.', 'error');
        }
    } catch (error) {
        console.error('Erreur de connexion:', error);
        showAlert('Erreur de connexion au serveur. Veuillez réessayer.', 'error');
    } finally {
        // Réactiver le bouton
        submitBtn.disabled = false;
        submitBtn.textContent = 'Se Connecter';
        loading.style.display = 'none';
    }
}

// Fonction pour afficher les alertes
function showAlert(message, type) {
    const alertContainer = document.getElementById('alert-container');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    alertContainer.innerHTML = '';
    alertContainer.appendChild(alertDiv);
    
    // Supprimer l'alerte après 5 secondes
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Fonction pour basculer la visibilité du mot de passe
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.querySelector('.password-toggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.textContent = '🙈';
    } else {
        passwordInput.type = 'password';
        toggleBtn.textContent = '👁️';
    }
}

// Validation email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Gestion du mot de passe oublié
function handleForgotPassword() {
    const email = document.getElementById('email').value.trim();
    
    if (!email) {
        showAlert('Veuillez d\'abord entrer votre adresse email.', 'info');
        document.getElementById('email').focus();
        return;
    }

    if (!isValidEmail(email)) {
        showAlert('Veuillez entrer une adresse email valide.', 'error');
        return;
    }

    // Simuler l'envoi d'un email de réinitialisation
    showAlert('Un email de réinitialisation a été envoyé à votre adresse.', 'info');
    // Ici vous pourriez faire un appel AJAX vers reset-password.php
}

// Gestion des touches clavier
// (optionnel, à activer si besoin)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        const activeElement = document.activeElement;
        if (activeElement.tagName === 'INPUT') {
            handleLogin();
        }
    }
});

// Pré-remplir avec les données de test en mode développement
if (window.location.hostname === 'localhost') {
    document.getElementById('email').value = 'admin@manege.local';
    document.getElementById('password').value = 'admin123';
    // Ajouter un bouton de test
    const testBtn = document.createElement('button');
    testBtn.textContent = 'Connexion Test';
    testBtn.type = 'button';
    testBtn.className = 'login-btn';
    testBtn.style.marginTop = '10px';
    testBtn.style.background = '#28a745';
    testBtn.onclick = function() {
        document.getElementById('email').value = 'admin@manege.local';
        document.getElementById('password').value = 'admin123';
        handleLogin();
    };
    document.querySelector('.login-container').appendChild(testBtn);
}
