🏥 Medica DB

**A Full-Stack Medical Workflow Management System**

---

## 📌 Overview

Medica DB is an end-to-end full-stack medical web application designed to streamline healthcare workflows. It enables doctors to manage patients, process medical images, collaborate via a professional forum, and monitor activities in real time — all within a single responsive interface.

This system bridges the gap between traditional hospital databases and modern, interactive clinical tools.

---

## 🎯 Motivation

Healthcare systems are evolving rapidly, but many platforms still lack:

* Real-time collaboration
* Intuitive user interfaces
* Integrated image processing

Medica DB was built to solve this by delivering a **lean, efficient, and user-friendly platform** that centralizes:

* Clinical data
* Medical imaging
* Professional communication

---

## ⚖️ Competitive Edge

Compared to existing systems like Practo, OpenMRS, and Cerner:

* 🎨 Canvas-based real-time image processing
* 💬 Integrated medical forum with case/image sharing
* 📊 Real-time dashboards for monitoring
* ⚡ Frontend local storage for faster performance

---

## 🚀 Features

### 🔐 Authentication System

* Doctor Login & Registration
* Real-time form validation
* Secure password confirmation
* Specialty-based registration

---

### 📊 Dashboard

* Personalized doctor profile
* Real-time stats (patients, posts, images)
* Quick navigation
* Responsive UI

---

### 👨‍⚕️ Patient Management

#### Active Patients

* Search & filter by severity
* Real-time vitals
* Medical history & images
* Color-coded priority system

#### Treated Patients

* Outcome tracking (Excellent → Poor)
* Detailed reports
* Follow-up scheduling
* Downloadable summaries

---

### 💬 Medical Forum

* Post case studies & insights
* Attach images (PC or patient database)
* Comment & like system
* Threaded discussions
* Specialty tagging

---

### 🖼️ Image Processing System

* Upload from PC or database
* Real-time enhancement controls:

  * CLAHE
  * Brightness / Contrast
  * Saturation
  * Rotation & Zoom
* Live preview via canvas
* Export & share images

---

### 🗂️ Processed Image Gallery

* Image archive with metadata
* Download / delete options
* Processing history tracking
* Patient association

---

### 🎨 UI/UX Design

* Medical-themed (cyan/teal)
* Fully responsive
* Card-based layout
* Smooth animations & modals

---

## 🧠 Technical Architecture

### ⚙️ Tech Stack

* **Frontend:** React (Component-based architecture)
* **State Management:** React state & local storage
* **Rendering:** HTML5 Canvas
* **Database:** MySQL

---

### 🗃️ Database Design

* Fully normalized (3NF)
* Modular structure
* Foreign key relationships
* Indexed for performance

#### Core Tables:

* `Department`
* `Doctor`
* `Patient`
* `Image`
* `ProcessedImage`
* `FeatureInterest`
* `UserLogin`

---

### 🔗 ERD Highlights

* Doctor ↔ Patient (1:N)
* Patient ↔ Image (1:N)
* Image ↔ ProcessedImage (1:N)
* Doctor ↔ ProcessedImage (1:N)

---

## 📈 Key Functional Queries

### Authentication

```sql
SELECT doctor_id, password_hash FROM Doctors WHERE email = ?;
```

### Patient Management

```sql
SELECT p.patient_id, p.name, COUNT(i.image_id)
FROM Patients p
LEFT JOIN Images i ON p.patient_id = i.patient_id
GROUP BY p.patient_id;
```

### Analytics

```sql
SELECT COUNT(*) FROM Patients;
SELECT COUNT(*) FROM Images;
```

---

## 📸 Screenshots

🔗 [View Application Screenshots](https://drive.google.com/drive/folders/1szX4yOhoqOjjQ20u8FoaDhevSVPWKwd6?usp=drive_link)

---

## 🎥 Demo Video

🔗 [Medica DB Demo Video](https://drive.google.com/drive/folders/1d2lel_rqnAuB9FNNyUlhttNUZRdqfKXu)

---

## 🔮 Future Improvements

* ☁️ Cloud storage (AWS S3 / Firebase)
* 🔐 OAuth & encryption
* 👥 Multi-role system (Admin, Nurse, etc.)
* 🔔 Notifications (email/SMS reminders)
* 📊 Admin dashboard & audit logs
* 🤖 AI-driven diagnostics

---

## 🧩 Project Structure

```
/src
 ├── components
 ├── pages
 ├── assets
 ├── utils
 └── App.js
```

---

## 👨‍💻 Contributors
* **Nusrat Jahan Bably**

## 📌 Conclusion

Medica DB is not just a project — it’s a **proof-of-concept for the future of digital healthcare systems**.

With its modular design and scalable architecture, it has the potential to evolve into a **real-world clinical solution**.

