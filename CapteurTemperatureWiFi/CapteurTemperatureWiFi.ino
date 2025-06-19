#include <WiFi.h>
#include <HTTPClient.h>
#include <math.h>
 
// --- Configuration WiFi ---
const char* ssid = "Isep NDL EAP";
const char* password = "RdzzKq3shxC8qvVX5J";
 
// --- Configuration Serveur ---
const char* serverURL = "http://10.2.152.27:5000";  // Remplacez par votre domaine Replit
const char* tivaId = "TIVA-001"; // ID unique de ce capteur (changez pour chaque carte)
 
// --- Constantes pour le capteur (votre code existant) ---
const int SENSOR_PIN = A0;
const double RESISTOR_FIXED = 10000.0;
const double ADC_RESOLUTION = 4096.0;
 
// --- Variables pour la gestion du temps ---
unsigned long lastSendTime = 0;
const unsigned long SEND_INTERVAL = 30000; // Envoyer toutes les 30 secondes
 
// Fonction pour convertir la valeur brute de l'ADC en température Celsius
// (Votre fonction existante, inchangée)
double Thermister(int RawADC) {
  double Temp;
 
  if (RawADC == 0) {
    return -999; // Retourner une valeur d'erreur
  }
 
  // Calculer la résistance de la thermistance
  double R_thermistor = RESISTOR_FIXED * ( (double)RawADC / (ADC_RESOLUTION - RawADC) );
 
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
 
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
 
  Serial.println();
  Serial.println("WiFi connecté!");
  Serial.print("Adresse IP: ");
  Serial.println(WiFi.localIP());
  Serial.print("ID Capteur: ");
  Serial.println(tivaId);
  Serial.println("Envoi des données vers le dashboard...");
}
 
void loop() {
  // Lire la température (votre code existant)
  int rawValue = analogRead(SENSOR_PIN);
  double temperature = Thermister(rawValue);
 
  // Affichage local (comme avant)
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
    return;
  }
 
  // Ignorer les valeurs d'erreur
  if (temperature == -999) {
    Serial.println("Erreur capteur, pas d'envoi au serveur");
    return;
  }
 
  HTTPClient http;
 
  // Construction de l'URL
  String url = String(serverURL) + "/api/sensors/" + String(tivaId) + "/reading";
 
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
 
  // Création du JSON payload
  String jsonPayload = "{\"temperature\":" + String(temperature, 2) + "}";
 
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
   
    if (httpResponseCode == 200) {
      Serial.println("✓ Données envoyées avec succès!");
    }
  } else {
    Serial.print("Erreur HTTP: ");
    Serial.println(httpResponseCode);
  }
 
  http.end();
}
}
