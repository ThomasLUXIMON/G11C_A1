// dashboard.js - Gestion du tableau de bord des manèges
// Utilise l'architecture MVC avec les routes définies dans Routes.php

// Variables globales pour les graphiques
let capaciteChart = null;
let temperatureChart = null;

// Données simulées pour la température (à remplacer par des vraies données de capteurs)
const temperatureSimulee = {
    'Grand 8': 24.5,
    'Tonneaux Volants': 23.2,
    'Train Fantôme': 22.8,
    'Chaises Volantes': 25.1,
    'Carrousel': 24.0,
    'Montagnes Russes': 23.7
};

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Charger les informations utilisateur
    loadUserInfo();
    
    // Charger les données du dashboard
    loadDashboardData();
    
    // Actualisation automatique toutes les 30 secondes
    setInterval(loadDashboardData, 30000);
});

// Fonction pour charger les informations utilisateur
function loadUserInfo() {
    // Les infos utilisateur sont stockées dans la session
    const userName = sessionStorage.getItem('user_name') || 'Utilisateur';
    const userRole = sessionStorage.getItem('user_role') || 'utilisateur';
    
    document.getElementById('user-name').textContent = userName;
    document.getElementById('user-role').textContent = capitalizeFirst(userRole);
}

// Fonction principale pour charger les données du dashboard
async function loadDashboardData() {
    try {
        // Afficher l'indicateur de chargement
        showLoading(true);
        
        // Charger les statistiques générales
        const statsResponse = await fetch('/G11C/G11C_A1/api/stats');
        const statsData = await statsResponse.json();
        
        if (statsData.success) {
            updateStatistiques(statsData.data);
        }
        
        // Charger les données des manèges
        const manegesResponse = await fetch('/G11C/G11C_A1/getManegesData');
        const manegesData = await manegesResponse.json();
        
        if (manegesData.success) {
            afficherManeges(manegesData.maneges);
            updateGraphiques(manegesData.maneges);
        }
        
    } catch (error) {
        console.error('Erreur lors du chargement des données:', error);
        showNotification('Erreur lors du chargement des données', 'error');
    } finally {
        showLoading(false);
    }
}

// Mise à jour des cartes de statistiques
function updateStatistiques(data) {
    document.getElementById('total-maneges').textContent = data.total_maneges || 0;
    document.getElementById('maneges-actifs').textContent = data.maneges_actifs || 0;
    
    // Calculer la capacité totale
    const capaciteTotale = data.maneges_status ? 
        data.maneges_status.reduce((sum, m) => sum + (m.capacite_max || 0), 0) : 0;
    document.getElementById('capacite-totale').textContent = capaciteTotale;
    
    // Calculer la température moyenne
    const temperatures = Object.values(temperatureSimulee);
    const tempMoyenne = temperatures.length > 0 ? 
        (temperatures.reduce((a, b) => a + b, 0) / temperatures.length).toFixed(1) : '0';
    document.getElementById('temperature-moyenne').textContent = tempMoyenne + '°C';
}

// Affichage des manèges
function afficherManeges(maneges) {
    const container = document.getElementById('maneges-container');
    
    if (!maneges || maneges.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">Aucun manège disponible</p>';
        return;
    }
    
    let html = '<div class="row">';
    
    maneges.forEach(manege => {
        const temperature = temperatureSimulee[manege.nom] || (20 + Math.random() * 8).toFixed(1);
        const statusClass = getStatusClass(manege.statut);
        const statusText = getStatusText(manege.statut);
        
        html += `
            <div class="col-md-6 col-lg-4">
                <div class="manege-card">
                    <div class="manege-header">
                        <div>
                            <h5 class="manege-name">${escapeHtml(manege.nom)}</h5>
                            <span class="manege-type">${escapeHtml(manege.type)}</span>
                        </div>
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </div>
                    
                    <div class="manege-info">
                        <div class="info-item">
                            <i class="fas fa-users info-icon"></i>
                            <span class="info-value">${manege.capacite_max}</span>
                            <span class="info-label">Capacité max</span>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-clock info-icon"></i>
                            <span class="info-value">${formatDuree(manege.duree_tour)}</span>
                            <span class="info-label">Durée tour</span>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-child info-icon"></i>
                            <span class="info-value">${manege.age_minimum}+</span>
                            <span class="info-label">Âge min</span>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-ruler-vertical info-icon"></i>
                            <span class="info-value">${manege.taille_minimum || 0} cm</span>
                            <span class="info-label">Taille min</span>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <span class="temperature-display">
                            <i class="fas fa-thermometer-half"></i>
                            ${temperature}°C
                        </span>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Mise à jour des graphiques
function updateGraphiques(maneges) {
    if (!maneges || maneges.length === 0) return;
    
    const labels = maneges.map(m => m.nom);
    const capacites = maneges.map(m => m.capacite_max);
    const temperatures = maneges.map(m => temperatureSimulee[m.nom] || (20 + Math.random() * 8).toFixed(1));
    
    // Graphique des capacités
    const ctxCapacite = document.getElementById('capaciteChart').getContext('2d');
    if (capaciteChart) capaciteChart.destroy();
    
    capaciteChart = new Chart(ctxCapacite, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Capacité maximale',
                data: capacites,
                backgroundColor: 'rgba(77, 171, 247, 0.7)',
                borderColor: 'rgba(77, 171, 247, 1)',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 5
                    }
                }
            }
        }
    });
    
    // Graphique des températures
    const ctxTemp = document.getElementById('temperatureChart').getContext('2d');
    if (temperatureChart) temperatureChart.destroy();
    
    temperatureChart = new Chart(ctxTemp, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Température (°C)',
                data: temperatures,
                borderColor: '#f97316',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                tension: 0.3,
                fill: true,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointBackgroundColor: '#f97316',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    min: 15,
                    max: 30,
                    ticks: {
                        callback: function(value) {
                            return value + '°C';
                        }
                    }
                }
            }
        }
    });
}

// Fonction pour rafraîchir le dashboard
function refreshDashboard() {
    showNotification('Actualisation en cours...', 'info');
    loadDashboardData();
}

// === Fonctions utilitaires ===

function getStatusClass(statut) {
    switch(statut) {
        case 'actif': return 'status-actif';
        case 'maintenance': return 'status-maintenance';
        case 'ferme': return 'status-ferme';
        default: return 'status-ferme';
    }
}

function getStatusText(statut) {
    switch(statut) {
        case 'actif': return 'Actif';
        case 'maintenance': return 'Maintenance';
        case 'ferme': return 'Fermé';
        default: return 'Inconnu';
    }
}

function formatDuree(secondes) {
    const minutes = Math.floor(secondes / 60);
    const sec = secondes % 60;
    return `${minutes}:${sec.toString().padStart(2, '0')}`;
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showLoading(show) {
    const container = document.getElementById('maneges-container');
    if (show && container.children.length > 0) {
        // Ne pas afficher le spinner si des données sont déjà présentes
        return;
    }
    
    if (show) {
        container.innerHTML = `
            <div class="text-center py-5">
                <div class="loading-spinner"></div>
                <p class="mt-3 text-muted">Chargement des manèges...</p>
            </div>
        `;
    }
}

function showNotification(message, type = 'info') {
    // Supprimer les notifications existantes
    const existingNotifs = document.querySelectorAll('.alert-notification');
    existingNotifs.forEach(notif => notif.remove());
    
    // Créer la nouvelle notification
    const notif = document.createElement('div');
    notif.className = `alert-notification alert-${type === 'error' ? 'error' : 'success'}`;
    notif.textContent = message;
    
    document.body.appendChild(notif);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        notif.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// === Gestion des sessions actives (optionnel) ===

async function loadSessionsActives() {
    try {
        const response = await fetch('/G11C/G11C_A1/api/sessions/active');
        const data = await response.json();
        
        if (data.success && data.sessions) {
            updateSessionsDisplay(data.sessions);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des sessions:', error);
    }
}

function updateSessionsDisplay(sessions) {
    // Mettre à jour l'affichage des sessions actives si nécessaire
    const sessionsCount = sessions.length;
    
    // Ajouter un indicateur de sessions actives sur les cartes de manège
    sessions.forEach(session => {
        const manegeCard = document.querySelector(`[data-manege-id="${session.manege_id}"]`);
        if (manegeCard) {
            manegeCard.classList.add('session-active');
        }
    });
}

// === Intégration avec les capteurs TIVA (si disponible) ===

async function loadTivaData() {
    try {
        const response = await fetch('/G11C/G11C_A1/api/tiva/realtime');
        const data = await response.json();
        
        if (data.success && data.capteurs) {
            updateCapteurData(data.capteurs);
        }
    } catch (error) {
        console.error('Erreur lors de la récupération des données TIVA:', error);
    }
}

function updateCapteurData(capteurs) {
    // Mettre à jour les températures réelles depuis les capteurs
    capteurs.forEach(capteur => {
        if (capteur.type === 'temperature' && capteur.manege_nom) {
            temperatureSimulee[capteur.manege_nom] = capteur.valeur;
        }
    });
    
    // Rafraîchir l'affichage
    loadDashboardData();
}

// === Gestion des alertes ===

async function checkAlertes() {
    try {
        const response = await fetch('/G11C/G11C_A1/api/alerts-count');
        const data = await response.json();
        
        if (data.success && data.count > 0) {
            showNotification(`${data.count} nouvelle(s) alerte(s) en attente`, 'warning');
        }
    } catch (error) {
        console.error('Erreur lors de la vérification des alertes:', error);
    }
}

// Vérifier les alertes toutes les minutes
setInterval(checkAlertes, 60000);

// === Export des fonctions pour utilisation externe ===
window.dashboardFunctions = {
    refresh: refreshDashboard,
    loadTivaData: loadTivaData,
    showNotification: showNotification
};