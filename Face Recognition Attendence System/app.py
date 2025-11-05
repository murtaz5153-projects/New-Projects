import cv2
import numpy as np
import face_recognition
import pandas as pd
import os
from datetime import datetime

# Initialize webcam
video_capture = cv2.VideoCapture(0)

# Load known faces
murtaza_image = face_recognition.load_image_file("faces/murtaza.jpg")
murtaza_encoding = face_recognition.face_encodings(murtaza_image)[0]

mustafa_image = face_recognition.load_image_file("faces/mustafa.jpg")
mustafa_encoding = face_recognition.face_encodings(mustafa_image)[0]

taha_image = face_recognition.load_image_file("faces/taha.jpg")
taha_encoding = face_recognition.face_encodings(taha_image)[0]

mustafan_image = face_recognition.load_image_file("faces/mustafa n.jpg")
mustafan_encoding = face_recognition.face_encodings(mustafan_image)[0]

alifiya_image = face_recognition.load_image_file("faces/alifiya.jpg")
alifiya_encoding = face_recognition.face_encodings(alifiya_image)[0]

# Store known faces and names
known_face_encodings = [
    murtaza_encoding,
    mustafa_encoding,
    taha_encoding,
    alifiya_encoding,
    mustafan_encoding
]
known_face_names = ["Murtaza", "Mustafa", "Taha", "Alifiya", "Mustafa.N"]

# Prepare attendance folder
if not os.path.exists("attendance"):
    os.makedirs("attendance")

# Create attendance file (date-wise)
today_date = datetime.now().strftime("%Y-%m-%d")
filename = f"attendance_{today_date}.csv"
attendance_path = os.path.join("attendance", filename)

# Create DataFrame if not exists
if not os.path.isfile(attendance_path):
    df = pd.DataFrame(columns=["Name", "Time"])
    df.to_csv(attendance_path, index=False)
else:
    df = pd.read_csv(attendance_path)

# Keep track of attendance
attendance_marked = set(df["Name"].tolist())

# Create folder for unknown faces
if not os.path.exists("unknown_faces"):
    os.makedirs("unknown_faces")

# Font style for OpenCV text
font = cv2.FONT_HERSHEY_SIMPLEX

print("🎥 Starting camera... Press 'q' to quit.")

while True:
    ret, frame = video_capture.read()
    if not ret:
        break

    # Resize for faster detection
    small_frame = cv2.resize(frame, (0, 0), fx=0.25, fy=0.25)
    rgb_small_frame = cv2.cvtColor(small_frame, cv2.COLOR_BGR2RGB)

    # Detect faces
    face_locations = face_recognition.face_locations(rgb_small_frame)
    face_encodings = face_recognition.face_encodings(rgb_small_frame, face_locations)

    for (top, right, bottom, left), face_encoding in zip(face_locations, face_encodings):
        matches = face_recognition.compare_faces(known_face_encodings, face_encoding)
        name = "Unknown"

        face_distances = face_recognition.face_distance(known_face_encodings, face_encoding)
        best_match_index = np.argmin(face_distances)

        if matches[best_match_index]:
            name = known_face_names[best_match_index]

        # Real-time attendance marking using pandas
        if name != "Unknown" and name not in attendance_marked:
            attendance_marked.add(name)
            current_time = datetime.now().strftime("%H:%M:%S")

            new_row = pd.DataFrame([[name, current_time]], columns=["Name", "Time"])
            df = pd.concat([df, new_row], ignore_index=True)
            df.to_csv(attendance_path, index=False)

            print(f"✅ Marked present: {name} at {current_time}")

        elif name == "Unknown":
            # Save snapshot of unknown person
            unknown_filename = f"unknown_{datetime.now().strftime('%H%M%S')}.jpg"
            cv2.imwrite(os.path.join("unknown_faces", unknown_filename), frame)
            print("⚠️ Unknown face detected!")

        # Draw boxes and labels
        top *= 4
        right *= 4
        bottom *= 4
        left *= 4

        color = (0, 255, 0) if name != "Unknown" else (0, 0, 255)
        cv2.rectangle(frame, (left, top), (right, bottom), color, 2)
        cv2.putText(frame, name, (left, bottom + 25), font, 1, color, 2)

    # Show live attendance count
    cv2.putText(frame, f"Total Present: {len(attendance_marked)}", (20, 40), font, 1, (0, 255, 0), 2)
    cv2.imshow("Attendance Dashboard", frame)

    if cv2.waitKey(1) & 0xFF == ord("q"):
        break

video_capture.release()
cv2.destroyAllWindows()

print("\n📁 Attendance saved in:", attendance_path)
