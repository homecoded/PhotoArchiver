# PhotoArchiver – Automatic Image Optimization and Backup

PhotoArchiver is a PHP-based web application for automatically optimizing, resizing, and backing up images.

***WARNING! This is experimental and may cause loss of data.***

## 📌 Features

- **Upload JPEG images**: Supports multiple files at once
- **Automatic image rotation**: Corrects orientation based on EXIF data
- **Resizing**: Reduces images to a maximum side length of 2000px
- **Preserves EXIF data**: Copies metadata to optimized images
- **Backup function**: Saves original images in a structured folder based on creation date
- **Security**: Users must log in
- **Cross-Origin support**: Allows CORS for easy API usage

## 🔧 Installation

1. Clone the repository:
   ```sh
   git clone https://github.com/your-user/photoarchiver.git  
   ```

2. Setup users
   ```sh
   php bin/user_create.php username=NewUser password=IamPasswordW00t
   ```
   Please replace "NewUser" and "IamPasswordW00t" with a proper passwor:

3. Setup webserver webroot to ```pub``` folder

## 🛠️ Requirements

- PHP 8.1 or later
- exiftool for metadata transfer
- Web server with file upload enabled

## Todos

- Logout button
- style back-button in login-failed screen
- language support
- download of original files / clearing storage (API)
- showing storage stats (how many original files are in the storage)
