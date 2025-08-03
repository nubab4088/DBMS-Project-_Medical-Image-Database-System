import os
import random
from datetime import date, timedelta, datetime

# Department Mappings from your MIDS.sql file
# 1: X-ray, 2: CT Scan, 3: MRI
DEPARTMENT_MAP = {
    # CT Scans (Dept 2)
    "Adenocarcinoma": 2,
    "Large.cell.carcinoma": 2,
    "NORMAL LUNG CT": 2,
    "Squamous": 2,
    "NORMAL BRAIN CT SCAN": 2,
    "TUMOR BRAIN CT SCAN": 2,
    # MRI (Dept 3)
    "Brain MRI": 3,
    # X-Ray (Dept 1)
    "Frac": 1,
    "Non Frac": 1,
    "COVID": 1,
    "Lung Opacity": 1,
    "Normal": 1,
    "Pneumonia": 1,
    "Tuberculosis": 1,
    # Ultrasound (Dept 4)
    "UltraSound": 4,
    # Ophthalmology (Dept 6)
    "Opthalmology": 6,
}

# Subcategory Mappings from your MIDS.sql file
SUBCATEGORY_MAP = {
    "Adenocarcinoma": 213,
    "Large.cell.carcinoma": 214,
    "NORMAL LUNG CT": 211,
    "Squamous": 212,
    "NORMAL BRAIN CT SCAN": 221,
    "TUMOR BRAIN CT SCAN": 222,
    "Healthy Brain": 311,
    "Tumor Brain": 312,
    "Frac": 122,
    "Non Frac": 121,
    "COVID": 114,
    "Lung Opacity": 115,
    "Normal": 111,
    "Pneumonia": 113,
    "Tuberculosis": 112,
    # New Ultrasound Subcategories
    "411": 411,
    "412": 412,
    "413": 413,
    "421": 421,
    "422": 422,
    "431": 431,
    "432": 432,
    "433": 433,
    # New Ophthalmology Subcategories
    "611": 611,
    "612": 612,
    "613": 613,
    "614": 614,
}

def generate_doctors():
    """Generate 30 doctors (5 per department) with proper IDs and admin assignments."""
    import random
    from datetime import datetime

    admin_names = [
        ("Nusrat Jahan", "Bably"),  # X-Ray
        ("Ahmad", "Mufti")         # CT Scan
    ]

    first_names = ["Sadia", "Imran", "Rafiq", "Faria", "Tanvir", "Lubna", "Kamrul", "Nazia", "Hossain", "Mehedi", "Shaila", "Tariq"]
    last_names = ["Rahman", "Hossain", "Alam", "Khan", "Akter", "Begum", "Chowdhury", "Islam", "Mufti", "Siddiqui", "Ahmed", "Bappy"]

    departments = {
        1: ("01", "xray"),
        2: ("02", "ct"),
        3: ("03", "mri"),
        4: ("04", "ultra"),
        5: ("05", "dentist"),
        6: ("06", "opthal")
    }

    total_doctors = 30
    doctors_per_department = total_doctors // len(departments)
    now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

    doctors = []
    doctor_count = 1

    for dept_id, (dept_code, email_tag) in departments.items():
        if dept_id == 1:
            first, last = admin_names[0]
        elif dept_id == 2:
            first, last = admin_names[1]
        else:
            first = random.choice(first_names)
            last = random.choice(last_names)

        doctor_serial = f"{dept_code}001"
        doctor_id = f"D{doctor_serial}"
        email = f"{last.lower()}{doctor_serial}@admin.{email_tag}.hospital.com"
        name = f"Dr. {first} {last}"
        password_hash = f"hash_{last.lower()}"
        access_level = "Admin"
        
        doctors.append({
            'doctor_id': doctor_id,
            'name': name,
            'email': email,
            'password_hash': password_hash,
            'access_level': access_level,
            'department_id': dept_id,
            'created_at': now
        })
        doctor_count += 1

        for i in range(2, doctors_per_department + 1):
            first = random.choice(first_names)
            last = random.choice(last_names)
            serial = f"{i:03d}"
            doctor_serial = f"{dept_code}{serial}"
            doctor_id = f"D{doctor_serial}"
            email = f"{last.lower()}{doctor_serial}@{email_tag}.mids.com"
            name = f"Dr. {first} {last}"
            password_hash = f"hash_{last.lower()}"
            access_level = "User"
            
            doctors.append({
                'doctor_id': doctor_id,
                'name': name,
                'email': email,
                'password_hash': password_hash,
                'access_level': access_level,
                'department_id': dept_id,
                'created_at': now
            })
            doctor_count += 1
    
    return doctors

def get_image_details(image_path):
    parts = image_path.split(os.sep)
    # path is like: 'images/Bably/UltraSound/411/411000003.png'
    # or 'images/Renamed Folders zips by Mufti/COVID/114000001.png'
    if len(parts) < 4:
        return None, None

    # Determine the correct parts based on the top-level folder
    if parts[1] == "Bably":
        # images/Bably/UltraSound/411/....
        dept_folder = parts[2]
        subcat_folder = parts[3]
    elif parts[1] == "Renamed Folders zips by Mufti":
        # images/Renamed Folders zips by Mufti/COVID/...
        # Some folders have an extra layer e.g. /Brain MRI/Healthy Brain/
        if parts[2] in ["Brain MRI", "NORMAL BRAIN CT SCAN", "TUMOR BRAIN CT SCAN"]:
             if len(parts) < 5:
                return None, None
             dept_folder = parts[2]
             subcat_folder = parts[3]
        else:
            dept_folder = parts[2]
            subcat_folder = parts[2] # In this case, subcategory is the same as department folder
    else:
        return None, None

    department_id = DEPARTMENT_MAP.get(dept_folder)
    subcategory_id = SUBCATEGORY_MAP.get(subcat_folder)

    return department_id, subcategory_id

def main():
    image_dir = "images"
    sql_statements = []
    
    # Clear existing data (but keep doctors)
    sql_statements.append("SET FOREIGN_KEY_CHECKS = 0;")
    sql_statements.append("TRUNCATE TABLE `Images`;")
    sql_statements.append("TRUNCATE TABLE `Patients`;")
    sql_statements.append("TRUNCATE TABLE `Doctors`;")
    sql_statements.append("SET FOREIGN_KEY_CHECKS = 1;")
    
    # Generate doctors using the improved logic
    doctors = generate_doctors()
    
    # Add doctor INSERT statements
    for doctor in doctors:
        sql_statements.append(
            f"INSERT INTO `Doctors` (`doctor_id`, `name`, `email`, `password_hash`, `access_level`, `department_id`, `created_at`) VALUES "
            f"('{doctor['doctor_id']}', '{doctor['name']}', '{doctor['email']}', '{doctor['password_hash']}', '{doctor['access_level']}', {doctor['department_id']}, '{doctor['created_at']}');"
        )
    
    # Group doctors by department for easy access
    doctors_by_dept = {}
    for doctor in doctors:
        dept_id = doctor['department_id']
        if dept_id not in doctors_by_dept:
            doctors_by_dept[dept_id] = []
        doctors_by_dept[dept_id].append(doctor['doctor_id'])
    
    # Track patient serial numbers per subcategory
    patient_serial_by_subcategory = {}
    
    # Process all images
    for root, _, files in os.walk(image_dir):
        for file in files:
            if not file.lower().endswith(('.png', '.jpg', '.jpeg')):
                continue

            full_path = os.path.join(root, file)
            
            department_id, subcategory_id = get_image_details(full_path)
            if not department_id or not subcategory_id:
                print(f"Skipping '{file}': Could not determine info from path.")
                continue

            # --- Generate IDs and Data ---
            
            # Initialize counter for this subcategory if not exists
            if subcategory_id not in patient_serial_by_subcategory:
                patient_serial_by_subcategory[subcategory_id] = 0
            
            patient_serial_by_subcategory[subcategory_id] += 1
            patient_serial = patient_serial_by_subcategory[subcategory_id]

            # 1. Patient ID: P<MM><YY><SubCatID><Serial>
            reg_date = datetime.now() - timedelta(days=random.randint(0, 365))
            reg_month_str = reg_date.strftime('%m')
            reg_year_str = reg_date.strftime('%y')
            patient_serial_str = f"{patient_serial:03d}"
            patient_id = f"P{reg_month_str}{reg_year_str}{subcategory_id}{patient_serial_str}"

            # 2. Image ID: <SubCatID><Status><PatientSerial>
            status = "000"  # '000' for running/new
            image_id = f"{subcategory_id}{status}{patient_serial_str}"
            
            # --- Create SQL statements ---
            
            # Assign a random doctor from the correct department
            if department_id in doctors_by_dept:
                doctor_id = random.choice(doctors_by_dept[department_id])
            else:
                print(f"Warning: No doctors found for department {department_id}")
                continue
            
            # Insert patient record with doctor assignment
            patient_name = f"Patient {subcategory_id}-{patient_serial}"
            dob = (date.today() - timedelta(days=random.randint(365*20, 365*70))).strftime('%Y-%m-%d')
            gender = random.choice(['Male', 'Female'])
            contact = f"contact.p{subcategory_id}.{patient_serial}@example.com"
            sql_statements.append(
                f"INSERT INTO `Patients` (`patient_id`, `name`, `date_of_birth`, `gender`, `contact_info`, `doctor_id`) VALUES "
                f"('{patient_id}', '{patient_name}', '{dob}', '{gender}', '{contact}', '{doctor_id}');"
            )
            
            # Insert image record with new IDs
            sql_statements.append(
                f"INSERT INTO `Images` (`image_id`, `patient_id`, `doctor_id`, `department_id`, `subcategory_id`, `image_path`, `image_type`, `description`, `upload_date`) VALUES "
                f"('{image_id}', '{patient_id}', '{doctor_id}', {department_id}, {subcategory_id}, '{full_path.replace(' ', '%20')}', 'Raw', 'Scanned image', '{reg_date.strftime('%Y-%m-%d %H:%M:%S')}');"
            )

    print(f"Generated {len(sql_statements)} SQL statements.")
    
    # Write to file
    with open('insert_images.sql', 'w') as f:
        for statement in sql_statements:
            f.write(statement + '\n')
    
    print("SQL statements written to insert_images.sql")

if __name__ == "__main__":
    main() 