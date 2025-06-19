#include <WiFi.h>
#include <HTTPClient.h>
#include <math.h>

// --- Configuration WiFi ---
const char* ssid = "Isep NDL EAP";
const char* password = "RdzzKq3shxC8qvVX5J";

// --- Configuration Serveur ---
// IMPORTANT: Remplacez cette URL par votre domaine AlwaysData
const char* serverURL = "https://appg1d.alwaysdata.net/G11C/G11C_A1";  // Votre domaine AlwaysData
const char* tivaId = "TIVA-001"; // ID unique de ce capteur (changez pour chaque carte)

// --- Constantes pour le capteur ---
const int SENSOR_PIN = A0;
const double RESISTOR_FIXED = 10000.0;
const double ADC_RESOLUTION = 4096.0;

// --- Variables pour la gestion du temps ---
unsigned long lastSendTime = 0;
const unsigned long SEND_INTERVAL = 30000; // Envoyer toutes les 30 secondes

// Fonction pour convertir la valeur brute de l'ADC en température Celsius
double Thermister(int RawADC) {
  double Temp;

  if (RawADC == 0) {
    return -999; // Retourner une valeur d'erreur
  }

  // Calculer la résistance de la thermistance
  double R_thermistor = RESISTOR_FIXED * ((double)RawADC / (ADC_RESOLUTION - RawADC));

  // Application de l'équation de Steinhart-Hart
  Temp = log(R_thermistor);
  Temp = 1.0 / (0.001129148 + (0.000234125 + (0.0000000876741 * Temp * Temp)) * Temp);

  // Conversion de Kelvin en Celsius
  Temp = Temp - 273.15;
  return Temp;
}

void setup() {
  // Initialise la communication série
  Serial.begin(9600);
  Serial.println("Démarrage du capteur TIVA - AttractionTech");

  // Connexion WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connexion WiFi");

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 60) {
    delay(500);
    Serial.print(".");
    attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println();
    Serial.println("WiFi connecté!");
    Serial.print("Adresse IP: ");
    Serial.println(WiFi.localIP());
    Serial.print("ID Capteur: ");
    Serial.println(tivaId);
    Serial.println("Envoi des données vers le dashboard...");
    
    // Test de l'API au démarrage
    testApiConnection();
  } else {
    Serial.println();
    Serial.println("Échec de la connexion WiFi!");
  }
}

void loop() {
  // Lire la température
  int rawValue = analogRead(SENSOR_PIN);
  double temperature = Thermister(rawValue);

  // Affichage local
  Serial.print("Temperature: ");
  Serial.print(temperature);
  Serial.println(" C");

  // Vérifier si c'est le moment d'envoyer au serveur
  unsigned long currentTime = millis();
  if (currentTime - lastSendTime >= SEND_INTERVAL) {
    sendTemperatureToServer(temperature);
    lastSendTime = currentTime;
  }

  // Attendre 1 seconde avant la prochaine mesure
  delay(1000);
}

void sendTemperatureToServer(double temperature) {
  // Vérifier la connexion WiFi
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi déconnecté, tentative de reconnexion...");
    WiFi.reconnect();
    delay(5000); // Attendre un peu avant de réessayer
    return;
  }

  // Ignorer les valeurs d'erreur
  if (temperature == -999) {
    Serial.println("Erreur capteur, pas d'envoi au serveur");
    return;
  }

  HTTPClient http;

  // Construction de l'URL complète
  String url = String(serverURL) + "/api/sensors/" + String(tivaId) + "/reading";

  Serial.print("URL: ");
  Serial.println(url);

  // Configuration HTTP avec support HTTPS
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.setTimeout(10000); // Timeout de 10 secondes

  // Création du JSON payload
  // Vous pouvez ajouter manege_id et siege_numero si nécessaire
  String jsonPayload = "{";
  jsonPayload += "\"temperature\":" + String(temperature, 2);
  // jsonPayload += ",\"manege_id\":1";  // Décommentez et modifiez si nécessaire
  // jsonPayload += ",\"siege_numero\":5"; // Décommentez et modifiez si nécessaire
  jsonPayload += "}";

  Serial.print("Envoi au serveur: ");
  Serial.println(jsonPayload);

  // Envoi de la requête POST
  int httpResponseCode = http.POST(jsonPayload);

  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.print("Réponse serveur (");
    Serial.print(httpResponseCode);
    Serial.print("): ");
    Serial.println(response);
    
    if (httpResponseCode == 200 || httpResponseCode == 201) {
      Serial.println("✓ Données envoyées avec succès!");
    } else if (httpResponseCode == 400) {
      Serial.println("✗ Erreur: Données invalides");
    } else if (httpResponseCode == 404) {
      Serial.println("✗ Erreur: Endpoint non trouvé");
    } else if (httpResponseCode == 500) {
      Serial.println("✗ Erreur serveur");
    }
  } else {
    Serial.print("Erreur HTTP: ");
    Serial.println(httpResponseCode);
    Serial.print("Erreur détaillée: ");
    Serial.println(http.errorToString(httpResponseCode));
    Serial.println("Vérifiez la connexion réseau et l'URL du serveur");
  }

  http.end();
}

// Fonction de test de l'API
void testApiConnection() {
  HTTPClient http;
  
  String testUrl = String(serverURL) + "/api/sensors/test";
  
  Serial.println("Test de connexion API...");
  Serial.print("URL de test: ");
  Serial.println(testUrl);
  
  http.begin(testUrl);
  http.setTimeout(10000);
  
  int httpResponseCode = http.GET();
  
  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.print("Test API - Code: ");
    Serial.println(httpResponseCode);
    Serial.print("Réponse: ");
    Serial.println(response);
    
    if (httpResponseCode == 200) {
      Serial.println("✓ API accessible et fonctionnelle!");
    }
  } else {
    Serial.print("Erreur test API: ");
    Serial.println(http.errorToString(httpResponseCode));
  }
  
  http.end();
}