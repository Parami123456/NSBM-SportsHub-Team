
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  student_id VARCHAR(30) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  created_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE clubs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  category VARCHAR(50) NOT NULL,
  tagline VARCHAR(120) NOT NULL,
  description VARCHAR(255) NOT NULL,
  about TEXT NOT NULL,
  emoji VARCHAR(12) NOT NULL,
  training_days VARCHAR(100) NOT NULL,
  training_time VARCHAR(80) NOT NULL,
  venue VARCHAR(120) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE memberships (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  club_id INT NOT NULL,
  status ENUM('pending', 'approved', 'rejected')
    NOT NULL DEFAULT 'pending',
  joined_at DATETIME NOT NULL,

  UNIQUE KEY one_membership (user_id, club_id),

  FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE,

  FOREIGN KEY (club_id)
    REFERENCES clubs(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE facilities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  capacity INT NOT NULL
) ENGINE=InnoDB;
CREATE TABLE equipment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  category VARCHAR(50) NOT NULL,
  description VARCHAR(255) NOT NULL,
  quantity INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  facility_id INT NOT NULL,
  booking_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  purpose VARCHAR(250) NOT NULL,
  status ENUM('pending', 'approved', 'rejected', 'cancelled')
    NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,

  FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE,

  FOREIGN KEY (facility_id)
    REFERENCES facilities(id)
) ENGINE=InnoDB;

CREATE TABLE equipment_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  equipment_id INT NOT NULL,
  booking_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  quantity INT NOT NULL,
  purpose VARCHAR(250) NOT NULL,
  status ENUM('pending', 'approved', 'rejected', 'cancelled')
    NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,

  FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE,

  FOREIGN KEY (equipment_id)
    REFERENCES equipment(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE training_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  club_id INT NOT NULL,
  title VARCHAR(120) NOT NULL,
  session_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  venue VARCHAR(120) NOT NULL,
  coach VARCHAR(100) NOT NULL,

  FOREIGN KEY (club_id)
    REFERENCES clubs(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  club_id INT NULL,
  title VARCHAR(150) NOT NULL,
  subtitle VARCHAR(180) NOT NULL DEFAULT '',
  event_type VARCHAR(40) NOT NULL,
  event_date DATE NOT NULL,
  event_time TIME NOT NULL,
  venue VARCHAR(120) NOT NULL,
  status ENUM('upcoming', 'completed', 'cancelled')
    NOT NULL DEFAULT 'upcoming',
  score VARCHAR(80) NULL,

  FOREIGN KEY (club_id)
    REFERENCES clubs(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE event_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('registered', 'cancelled', 'attended') NOT NULL DEFAULT 'registered',
  registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY one_event_registration (user_id, event_id),

  FOREIGN KEY (event_id)
    REFERENCES events(id)
    ON DELETE CASCADE,

  FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;


INSERT INTO clubs
  (name, category, tagline, description, about, emoji,
   training_days, training_time, venue)
VALUES
  (
    'Cricket',
    'Team sport',
    'TEAMWORK · PRECISION',
    'Step up to the crease.',
    'Join the NSBM cricket community for coaching, friendly games and inter-faculty competition. All skill levels are welcome.',
    '🏏',
    'Monday, Wednesday, Saturday',
    '4:00 PM – 6:00 PM',
    'NSBM Cricket Ground'
  ),
  (
    'Basketball',
    'Team sport',
    'SPEED · SKILL',
    'Take your shot.',
    'Build your game with the campus basketball community. Sessions focus on skills, teamwork and league play.',
    '🏀',
    'Tuesday, Thursday',
    '4:30 PM – 6:30 PM',
    'Indoor Sports Complex'
  ),
  (
    'Football',
    'Team sport',
    'PASSION · UNITY',
    'Play for the badge.',
    'Meet your squad, sharpen your skills and represent your faculty in campus tournaments.',
    '⚽',
    'Monday, Friday',
    '4:00 PM – 6:00 PM',
    'Main Grounds'
  ),
  (
    'Swimming',
    'Individual sport',
    'PACE · ENDURANCE',
    'Find your rhythm.',
    'Train with qualified coaches in the campus swimming community.',
    '🏊',
    'Tuesday, Saturday',
    '3:30 PM – 5:00 PM',
    'Olympic-size Swimming Pool'
  ),
  (
    'Badminton',
    'Racquet sport',
    'FOCUS · FOOTWORK',
    'Own every rally.',
    'Friendly games and coached training for players at every level.',
    '🏸',
    'Wednesday, Friday',
    '4:00 PM – 6:00 PM',
    'Indoor Sports Complex'
  ),
  (
    'Athletics',
    'Individual sport',
    'DISCIPLINE · DRIVE',
    'Go beyond the finish.',
    'Track and field training for sprinters, distance runners and field athletes.',
    '🏃',
    'Monday, Thursday',
    '5:00 PM – 6:30 PM',
    'NSBM Track'
  );

INSERT INTO facilities (name, description, capacity)
VALUES
  (
    'NSBM International Cricket Stadium',
    'Full-size cricket ground with pavilion and practice nets.',
    500
  ),
  (
    'Indoor Sports Complex',
    'Multi-use indoor courts for basketball, badminton and volleyball.',
    180
  ),
  (
    'Olympic-size Swimming Pool',
    'Competition pool with changing rooms and spectator seating.',
    120
  ),
  (
    'Gymnasium & Fitness Center',
    'Strength and conditioning equipment for supervised sessions.',
    80
  ),
  (
    'Main Grounds',
    'Open multi-purpose field for football, rugby and athletics.',
    350
  );

INSERT INTO equipment (name, category, description, quantity)
VALUES
  ('Cricket bats', 'Cricket', 'Practice bats for cricket training and matches.', 8),
  ('Cricket balls', 'Cricket', 'Training cricket balls for team practice.', 20),
  ('Basketballs', 'Basketball', 'Basketballs for indoor practice and games.', 12),
  ('Football balls', 'Football', 'Football balls for team training and matches.', 12),
  ('Badminton rackets', 'Badminton', 'Rackets for campus badminton practice.', 12),
  ('Training cones', 'Training', 'Marker cones for drills and warm-up sessions.', 30),
  ('Volleyballs', 'Volleyball', 'Volleyballs for team training and games.', 8);
INSERT INTO events
  (club_id, title, subtitle, event_type, event_date,
   event_time, venue, status)
VALUES
  (
    1,
    'Inter-Faculty Cricket Championship',
    'Computing vs Business',
    'Tournament',
    '2026-11-12',
    '14:00:00',
    'Main Grounds',
    'upcoming'
  ),
  (
    2,
    'Campus Basketball League',
    'NSBM Lions vs SLIT',
    'Match',
    '2026-11-14',
    '16:30:00',
    'Indoor Court',
    'upcoming'
  ),
  (
    3,
    'Inter-Faculty Football Cup',
    'Group stage · Matchday 2',
    'Tournament',
    '2026-11-19',
    '15:00:00',
    'Main Grounds',
    'upcoming'
  );

INSERT INTO training_sessions
  (club_id, title, session_date, start_time, end_time, venue, coach)
VALUES
  (
    1,
    'Batting & fielding practice',
    '2026-11-02',
    '16:00:00',
    '18:00:00',
    'NSBM Cricket Ground',
    'Club Coach'
  ),
  (
    2,
    'Team skills session',
    '2026-11-03',
    '16:30:00',
    '18:30:00',
    'Indoor Sports Complex',
    'Club Coach'
  ),
  (
    3,
    'Tactical team training',
    '2026-11-06',
    '16:00:00',
    '18:00:00',
    'Main Grounds',
    'Club Coach'
  );

-- Register through the app first, then grant admin access:
-- UPDATE users
-- SET role = 'admin'
-- WHERE email = 'your-email@nsbm.ac.lk';