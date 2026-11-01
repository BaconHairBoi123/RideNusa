import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../api_config.dart';
import '../Models/motorcycle.dart';
import 'package:flutter/foundation.dart';

class MotorcycleService {
  Future<List<Motorcycle>> getMotorcycles() async {
  try {
    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/motorcycles'),
      headers: {
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final Map<String, dynamic> responseData =
          json.decode(response.body);

      if (responseData['status'] == 'success') {
        final List<dynamic> data = responseData['data'];
        return data.map((json) => Motorcycle.fromJson(json)).toList();
      } else {
        throw Exception(
          'Failed to load motorcycles: ${responseData['message']}',
        );
      }
    } else {
      throw Exception(
        'Failed to load motorcycles. Status Code: ${response.statusCode}',
      );
    }
  } catch (e) {
    throw Exception('Network error: $e');
  }
}

  Future<List<dynamic>> getMotorcycleServices({String? licensePlate}) async {
    try {
      String url = '${ApiConfig.baseUrl}/motorcycles/services';
      if (licensePlate != null && licensePlate.trim().isNotEmpty) {
        url += '?license_plate=${Uri.encodeComponent(licensePlate.trim())}';
      }
      final response = await http.get(Uri.parse(url));

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        if (responseData['status'] == 'success') {
          return responseData['data'] as List<dynamic>;
        }
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  Future<Map<String, dynamic>?> getMotorcycleGps(int motorcycleId) async {
    try {
      final response = await http.get(Uri.parse('${ApiConfig.baseUrl}/gps/history/$motorcycleId'));

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        if (responseData['status'] == 'success') {
          return responseData['data'];
        }
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  Future<Map<String, dynamic>> updateMotorcycleRelay(int motorcycleId, String relayStatus) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) {
        return {'success': false, 'message': 'You must be logged in.'};
      }

      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/gps/relay'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: json.encode({
          'motorcycle_id': motorcycleId,
          'relay_status': relayStatus,
        }),
      );

      final Map<String, dynamic> responseData = json.decode(response.body);
      if (response.statusCode == 200 && responseData['status'] == 'success') {
        return {
          'success': true,
          'message': responseData['message'] ?? 'Relay status updated successfully.',
          'data': responseData['data'],
        };
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Failed to update relay status.',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Connection error occurred.',
      };
    }
  }
}
