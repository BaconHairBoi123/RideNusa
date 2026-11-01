import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../api_config.dart';

class AuthService {
  Future<bool> login(String login, String password) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/login'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: json.encode({'login': login, 'password': password}),
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        if (responseData['status'] == 'success') {
          // Simpan token dari data access_token
          final String token = responseData['data']['access_token'];
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('auth_token', token);
          return true;
        }
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  Future<Map<String, dynamic>> register({
    required String name,
    required String username,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String phoneNumber,
    required String address,
    required String verificationType,
    String? licensePhotoPath,
    String? facePhotoPath,
  }) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('${ApiConfig.baseUrl}/register'),
      );

      // Add Headers
      request.headers.addAll({
        'Accept': 'application/json',
      });

      // Add text fields
      request.fields['name'] = name;
      request.fields['username'] = username;
      request.fields['email'] = email;
      request.fields['password'] = password;
      request.fields['password_confirmation'] = passwordConfirmation;
      request.fields['phone_number'] = phoneNumber;
      request.fields['address'] = address;
      request.fields['verification_type'] = verificationType;

      // Face photo is required for both SIM and course registration.
      if (facePhotoPath != null && facePhotoPath.isNotEmpty) {
        request.files.add(
          await http.MultipartFile.fromPath('face_photo', facePhotoPath),
        );
      }

      // License photo is only uploaded when the user selects SIM verification.
      if (verificationType == 'sim' &&
          licensePhotoPath != null &&
          licensePhotoPath.isNotEmpty) {
        request.files.add(
          await http.MultipartFile.fromPath('license_photo', licensePhotoPath),
        );
      }

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      final Map<String, dynamic> responseData = json.decode(response.body);
      if (response.statusCode == 201 && responseData['status'] == 'success') {
        final String token = responseData['data']['access_token'];
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', token);
        return {'success': true, 'message': responseData['message'] ?? 'Registration successful'};
      } else {
        return {'success': false, 'message': responseData['message'] ?? 'Registration failed', 'errors': responseData['errors']};
      }
    } catch (e) {
      return {'success': false, 'message': 'An error occurred during registration. Please try again.'};
    }
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
  }

  Future<Map<String, dynamic>?> getProfile() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) return null;

      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/user'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        if (responseData['status'] == 'success') {
          return responseData['data'];
        }
      }
      return null;
    } catch (_) {
      return null;
    }
  }

  Future<bool> isLoggedIn() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.containsKey('auth_token');
  }

  Future<Map<String, dynamic>> forgotPassword(String email) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/forgot-password'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: json.encode({'email': email}),
      );

      final Map<String, dynamic> responseData = json.decode(response.body);
      if (response.statusCode == 200 && responseData['status'] == 'success') {
        return {'success': true, 'message': responseData['message']};
      } else {
        return {'success': false, 'message': responseData['message'] ?? 'Failed to send reset link.'};
      }
    } catch (e) {
      return {'success': false, 'message': 'An error occurred. Please check your connection.'};
    }
  }

  Future<Map<String, dynamic>> updateVerification({
    required String licensePhotoPath,
    String? facePhotoPath,
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) {
        return {'success': false, 'message': 'No authentication token found.'};
      }

      final request = http.MultipartRequest(
        'POST',
        Uri.parse('${ApiConfig.baseUrl}/user/update-verification'),
      );

      // Add Headers
      request.headers.addAll({
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      });

      // Add file fields
      request.files.add(
        await http.MultipartFile.fromPath('license_photo', licensePhotoPath),
      );

      if (facePhotoPath != null && facePhotoPath.isNotEmpty) {
        request.files.add(
          await http.MultipartFile.fromPath('face_photo', facePhotoPath),
        );
      }

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      final Map<String, dynamic> responseData = json.decode(response.body);
      if (response.statusCode == 200 && responseData['status'] == 'success') {
        return {'success': true, 'message': responseData['message'] ?? 'Verification updated successfully'};
      } else {
        return {'success': false, 'message': responseData['message'] ?? 'Failed to update verification', 'errors': responseData['errors']};
      }
    } catch (e) {
      return {'success': false, 'message': 'An error occurred during verification update.'};
    }
  }
}
