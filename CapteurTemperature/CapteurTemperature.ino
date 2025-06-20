// Capteur de température pour LaunchPad Tiva C TM4C123
// Utilise le capteur de température interne ou un capteur externe

// Pour un capteur LM35 (optionnel - connecté sur A0)
const int tempPin = A0;  // Broche analogique pour LM35

// Variables
float temperature = 0;
float voltage = 0;

void setup() {
  // Initialisation de la communication série
  Serial.begin(9600);
  
  // Configuration de la LED rouge interne pour indication
  pinMode(RED_LED, OUTPUT);
  
  Serial.println("Capteur de température démarré");
  Serial.println("----------------------------");
}

void loop() {
  // Option 1: Lecture du capteur de température interne du TM4C123
  temperature = readInternalTemp();
  
  Serial.print("Température interne: ");
  Serial.print(temperature);
  Serial.println(" °C");
  
  // Option 2: Si vous avez un capteur LM35 externe (décommentez)
  /*
  int sensorValue = analogRead(tempPin);
  voltage = sensorValue * (3.3 / 4095.0);  // TM4C123 utilise 3.3V et 12-bit ADC
  float tempLM35 = voltage * 100.0;  // LM35: 10mV/°C
  
  Serial.print("Température LM35: ");
  Serial.print(tempLM35);
  Serial.println(" °C");
  */
  
  // Clignotement LED pour indiquer l'activité
  digitalWrite(RED_LED, HIGH);
  delay(100);
  digitalWrite(RED_LED, LOW);
  
  // Attendre 2 secondes avant la prochaine lecture
  delay(2000);
}

// Fonction pour lire la température interne du TM4C123
float readInternalTemp() {
  // Le TM4C123 a un capteur de température intégré
  // Cette fonction utilise l'ADC interne pour lire la température
  
  // Formule du fabricant pour convertir la valeur ADC en température
  // Température (°C) = 147.5 - ((75 * (VREFP - VREFN) * ADCVALUE) / 4096)
  
  // Pour simplifier, on utilise une approximation
  // Note: Pour une précision maximale, consultez la datasheet du TM4C123
  
  // Lecture simulée pour l'exemple
  // En pratique, vous devriez configurer l'ADC pour lire le capteur interne
  float adcValue = analogRead(TEMPSENSOR);  // Capteur interne
  float temp = 147.5 - ((75.0 * 3.3 * adcValue) / 4096.0);
  
  return temp;
}