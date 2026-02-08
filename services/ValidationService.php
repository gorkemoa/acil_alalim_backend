<?php
// services/ValidationService.php

class ValidationService {
    public static function validateRegister($data) {
        if (empty($data['name']) || empty($data['surname']) || empty($data['email']) || empty($data['password'])) {
            return "Name, surname, email and password are required.";
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format.";
        }
        if (strlen($data['password']) < 6) {
            return "Password must be at least 6 characters.";
        }
        if (!empty($data['phone']) && strlen($data['phone']) > 30) {
            return "Phone must be shorter than 30 characters.";
        }
        return true;
    }

    public static function validateNeed($data) {
        if (empty($data['title']) || strlen($data['title']) < 5) {
            return "Title must be at least 5 characters.";
        }
        if (empty($data['description']) || strlen($data['description']) < 10) {
            return "Description must be at least 10 characters.";
        }
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            return "Latitude and longitude are required.";
        }
        if (empty($data['province_id']) || empty($data['district_id'])) {
            return "Province and district are required.";
        }
        if (empty($data['category_id'])) {
            return "Category is required.";
        }
        return true;
    }
}
