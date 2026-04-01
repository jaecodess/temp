CREATE DATABASE IF NOT EXISTS statik;
USE statik;

-- Members
CREATE TABLE IF NOT EXISTS members (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(50)  NOT NULL,
    username       VARCHAR(20)  NOT NULL UNIQUE,
    email          VARCHAR(50)  NOT NULL UNIQUE,
    password       VARCHAR(255) NOT NULL,
    role           ENUM('user','admin') NOT NULL DEFAULT 'user',
    email_verified TINYINT NOT NULL DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Genre / event-type filter (e.g. Concert, Musical, Theatre, Dance, Comedy)
CREATE TABLE IF NOT EXISTS genres (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

-- Performances (the events on sale)
CREATE TABLE IF NOT EXISTS performances (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT         NOT NULL,
    venue       VARCHAR(100) NOT NULL,
    event_date  DATE         NOT NULL,
    event_time  TIME         NOT NULL,
    img_name    VARCHAR(255),
    genre_id    INT,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE SET NULL
);

-- Ticket categories per performance (Cat 1 / Cat 2 / Cat 3)
CREATE TABLE IF NOT EXISTS ticket_categories (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    performance_id   INT           NOT NULL,
    name             VARCHAR(20)   NOT NULL,   -- 'Cat 1', 'Cat 2', 'Cat 3'
    price            DECIMAL(10,2) NOT NULL,
    total_seats      INT           NOT NULL,
    available_seats  INT           NOT NULL,
    UNIQUE KEY unique_cat (performance_id, name),
    FOREIGN KEY (performance_id) REFERENCES performances(id) ON DELETE CASCADE
);

-- Shopping cart
CREATE TABLE IF NOT EXISTS cart_items (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    member_id           INT NOT NULL,
    ticket_category_id  INT NOT NULL,
    quantity            INT NOT NULL DEFAULT 1,
    UNIQUE KEY unique_cart_entry (member_id, ticket_category_id),
    FOREIGN KEY (member_id)          REFERENCES members(id)           ON DELETE CASCADE,
    FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON DELETE CASCADE
);

-- OTP tokens (one active code per email per purpose)
CREATE TABLE IF NOT EXISTS otp_tokens (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(100) NOT NULL,
    code       CHAR(6)      NOT NULL,
    purpose    ENUM('verify_email','reset_password','admin_confirm') NOT NULL,
    expires_at DATETIME     NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_otp (email, purpose)
);

-- Order history
CREATE TABLE IF NOT EXISTS order_items (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    member_id           INT           NOT NULL,
    ticket_category_id  INT           NOT NULL,
    quantity            INT           NOT NULL,
    price               DECIMAL(10,2) NOT NULL,   -- snapshot at purchase time
    order_id            VARCHAR(100),
    transaction_id      VARCHAR(100),
    order_date          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id)          REFERENCES members(id)           ON DELETE CASCADE,
    FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON DELETE CASCADE
);

-- Sample data: Members (password = Admin@1234 for all; email_verified = 1 so they skip the banner)
INSERT INTO members (name, username, email, password, role, email_verified) VALUES
    ('Billy Bompipi', 'billy', 'billy@ticketsg.com', '$2a$12$mPG0LpgqBp2DquVtU1aXEu4uALE1oiwIHnjxwA4E3o.1D13djlAdi', 'user',  1),
    ('Alice Tan',     'alice', 'alice@ticketsg.com', '$2a$12$mPG0LpgqBp2DquVtU1aXEu4uALE1oiwIHnjxwA4E3o.1D13djlAdi', 'user',  1),
    ('Teddy Admin',   'admin', 'admin@ticketsg.com', '$2a$12$mPG0LpgqBp2DquVtU1aXEu4uALE1oiwIHnjxwA4E3o.1D13djlAdi', 'admin', 1);

-- Sample data: Genres
INSERT INTO genres (name) VALUES
    ('Concert'), ('Musical'), ('Theatre'), ('Dance'), ('Comedy');

-- Sample data: Performances
INSERT INTO performances (id, name, description, venue, event_date, event_time, img_name, genre_id) VALUES
    (1, 'IDLE World Tour – Singapore',
     'K-pop girl group IDLE makes their Singapore debut on their world tour. Featuring hit tracks from their latest album.',
     'Singapore Indoor Stadium', '2026-06-14', '19:30:00', 'idle_world_tour.jpg', 1),

    (2, 'Hamilton: The Musical',
     'Lin-Manuel Miranda''s award-winning musical about the life of American Founding Father Alexander Hamilton. Performed by the original touring cast.',
     'Sands Theatre, Marina Bay Sands', '2026-07-05', '20:00:00', 'hamilton.jpg', 2),

    (3, 'BTS Yet To Come Concert',
     'BTS returns to Singapore for an electrifying night of their greatest hits spanning their entire career.',
     'National Stadium', '2026-08-22', '19:00:00', 'bts_concert.jpg', 1),

    (4, 'Swan Lake – Singapore Ballet',
     'The Singapore Ballet presents Tchaikovsky''s timeless masterpiece in a stunning full-length production.',
     'Esplanade Theatre', '2026-05-30', '19:30:00', 'swan_lake.jpg', 4),

    (5, 'The Lion King Musical',
     'Disney''s beloved stage adaptation comes to Singapore, featuring breathtaking costumes, puppetry, and iconic songs.',
     'Sands Theatre, Marina Bay Sands', '2026-09-10', '19:30:00', 'lion_king.jpg', 2),

    (6, 'Comedy Night Live SG',
     'A night of stand-up comedy featuring Singapore''s top comedians. Adults only.',
     'Capitol Theatre', '2026-04-19', '20:00:00', 'comedy_night.jpg', 5);

-- Sample data: Ticket categories (Cat 1 = best seats, Cat 3 = most affordable)
INSERT INTO ticket_categories (performance_id, name, price, total_seats, available_seats) VALUES
    -- IDLE World Tour
    (1, 'Cat 1', 288.00, 500,  500),
    (1, 'Cat 2', 168.00, 800,  800),
    (1, 'Cat 3',  88.00, 1200, 1200),
    -- Hamilton
    (2, 'Cat 1', 248.00, 300, 300),
    (2, 'Cat 2', 158.00, 500, 500),
    (2, 'Cat 3',  98.00, 700, 700),
    -- BTS Yet To Come
    (3, 'Cat 1', 388.00, 1000, 1000),
    (3, 'Cat 2', 248.00, 2000, 2000),
    (3, 'Cat 3', 128.00, 3000, 3000),
    -- Swan Lake
    (4, 'Cat 1', 178.00, 200, 200),
    (4, 'Cat 2', 118.00, 300, 300),
    (4, 'Cat 3',  68.00, 400, 400),
    -- The Lion King
    (5, 'Cat 1', 228.00, 300, 300),
    (5, 'Cat 2', 148.00, 500, 500),
    (5, 'Cat 3',  88.00, 700, 700),
    -- Comedy Night Live SG
    (6, 'Cat 1', 128.00, 150, 150),
    (6, 'Cat 2',  88.00, 250, 250),
    (6, 'Cat 3',  58.00, 350, 350);
