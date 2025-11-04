import speech_recognition as sr
import webbrowser
import pyttsx3
import musicLibrary
import requests
from openai import OpenAI
from gtts import gTTS
import pygame
import os
import datetime
import wikipedia
import pyjokes
import subprocess

recognizer = sr.Recognizer()
engine = pyttsx3.init()
newsapi = "d97de1c27d1a4509baea53025d81b927"
weather_api = "f2ac5cd0433b4e9114ab44f5001f4d88"  # get one free from https://openweathermap.org/api

def speak_old(text):
    engine.say(text)
    engine.runAndWait()

def speak(text):
    tts = gTTS(text)
    tts.save('temp.mp3')
    pygame.mixer.init()
    pygame.mixer.music.load('temp.mp3')
    pygame.mixer.music.play()
    while pygame.mixer.music.get_busy():
        pygame.time.Clock().tick(10)
    pygame.mixer.music.unload()
    os.remove("temp.mp3")

def aiProcess(command):
    client = OpenAI(api_key="<Your Key Here>")
    completion = client.chat.completions.create(
        model="gpt-3.5-turbo",
        messages=[
            {"role": "system", "content": "You are a virtual assistant named jarvis skilled in general tasks like Alexa and Google Cloud. Give short responses please"},
            {"role": "user", "content": command}
        ]
    )
    return completion.choices[0].message.content

def processCommand(c):
    c = c.lower()
    if "open google" in c:
        webbrowser.open("https://google.com")
    elif "open facebook" in c:
        webbrowser.open("https://facebook.com")
    elif "open youtube" in c:
        webbrowser.open("https://youtube.com")
    elif "open linkedin" in c:
        webbrowser.open("https://linkedin.com")
    elif "open instagram" in c:
        webbrowser.open("https://instagram.com")
    elif "open gmail" in c:
        webbrowser.open("https://mail.google.com")

    elif c.startswith("play"):
        song = c.split(" ")[1]
        link = musicLibrary.music.get(song)
        if link:
            webbrowser.open(link)
            speak(f"Playing {song}")
        else:
            speak("Song not found in your library.")

    elif "news" in c:
        r = requests.get(f"https://newsapi.org/v2/top-headlines?country=in&apiKey={newsapi}")
        if r.status_code == 200:
            data = r.json()
            articles = data.get('articles', [])
            for article in articles[:5]:
                speak(article['title'])
        else:
            speak("Sorry, I could not fetch the news right now.")

    elif "time" in c:
        now = datetime.datetime.now().strftime("%H:%M")
        speak(f"The time is {now}")

    elif "date" in c:
        today = datetime.date.today().strftime("%B %d, %Y")
        speak(f"Today's date is {today}")

    elif "weather" in c:
       try:
        # Extract city name dynamically
        words = c.split()
        if "in" in words:
            city_index = words.index("in") + 1
            city = " ".join(words[city_index:])
        else:
            speak("Which city should I check?")
            with sr.Microphone() as source:
                print("Listening for city name...")
                audio = recognizer.listen(source)
                city = recognizer.recognize_google(audio)
        
        # Fetch weather
        url = f"http://api.openweathermap.org/data/2.5/weather?q={city}&appid={weather_api}&units=metric"
        res = requests.get(url)
        data = res.json()

        if data.get("main"):
            temp = data["main"]["temp"]
            desc = data["weather"][0]["description"]
            speak(f"The temperature in {city} is {temp} degrees Celsius with {desc}.")
        else:
            speak(f"Sorry, I couldn't find the weather for {city}.")
    
       except Exception as e:
        speak("Sorry, there was an error while fetching the weather.")
        print(e)

    elif "joke" in c:
        joke = pyjokes.get_joke()
        speak(joke)

    elif "open notepad" in c:
        subprocess.Popen(["notepad.exe"])
        speak("Opening Notepad")

    elif "open calculator" in c:
        subprocess.Popen(["calc.exe"])
        speak("Opening Calculator")

    elif "stop" in c or "exit" in c or "quit" in c:
        speak("Goodbye! Have a great day.")
        exit()

    else:
        output = aiProcess(c)
        speak(output)

if __name__ == "__main__":
    speak("Initializing ....")
    while True:
        r = sr.Recognizer()
        print("recognizing...")
        try:
            with sr.Microphone() as source:
                print("Listening...")
                audio = r.listen(source, timeout=2, phrase_time_limit=1)
            word = r.recognize_google(audio)
            if word.lower() == "hello":
                speak("Yes how may i help you")
                with sr.Microphone() as source:
                    print("MURZA Active...")
                    audio = r.listen(source)
                    command = r.recognize_google(audio)
                    processCommand(command)
        except Exception as e:
            print("Error; {0}".format(e))
