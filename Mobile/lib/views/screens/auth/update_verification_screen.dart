import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../../../core/app_theme.dart';
import '../../../core/dialog_helper.dart';
import '../../../REST-API/Services/auth_service.dart';

class UpdateVerificationScreen extends StatefulWidget {
  const UpdateVerificationScreen({super.key});

  @override
  State<UpdateVerificationScreen> createState() => _UpdateVerificationScreenState();
}

class _UpdateVerificationScreenState extends State<UpdateVerificationScreen> {
  final AuthService _authService = AuthService();
  final ImagePicker _imagePicker = ImagePicker();

  String? _licensePhotoPath;
  String? _facePhotoPath;
  bool _isLoading = false;

  Future<void> _pickImage(String type) async {
    try {
      final XFile? image = await _imagePicker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 85,
      );
      if (image != null) {
        setState(() {
          if (type == 'license') {
            _licensePhotoPath = image.path;
          } else if (type == 'face') {
            _facePhotoPath = image.path;
          }
        });
      }
    } catch (e) {
      if (!mounted) return;
      DialogHelper.showMessage(
        context: context,
        message: 'Failed to pick image: $e',
        isError: true,
      );
    }
  }

  void _submitUpdate() async {
    if (_licensePhotoPath == null || _licensePhotoPath!.isEmpty) {
      DialogHelper.showMessage(
        context: context,
        message: "Please upload your Driver's License (SIM C) photo.",
        isError: true,
      );
      return;
    }

    if (_facePhotoPath == null || _facePhotoPath!.isEmpty) {
      DialogHelper.showMessage(
        context: context,
        message: "Please upload your Face Selfie photo.",
        isError: true,
      );
      return;
    }

    setState(() => _isLoading = true);

    final result = await _authService.updateVerification(
      licensePhotoPath: _licensePhotoPath!,
      facePhotoPath: _facePhotoPath,
    );

    setState(() => _isLoading = false);

    if (mounted) {
      if (result['success'] == true) {
        showDialog(
          context: context,
          barrierDismissible: false,
          builder: (ctx) => AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: const Row(
              children: [
                Icon(Icons.check_circle_outline, color: Colors.green, size: 28),
                SizedBox(width: 10),
                Text(
                  'Success',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                ),
              ],
            ),
            content: const Text(
              'Your verification documents have been updated successfully and are pending review.',
              style: TextStyle(color: AppTheme.darkColor, fontSize: 14),
            ),
            actions: [
              TextButton(
                onPressed: () {
                  Navigator.pop(ctx); // Close Dialog
                  Navigator.pop(context, true); // Pop back with result
                },
                child: const Text(
                  'OK',
                  style: TextStyle(
                    color: AppTheme.primaryColor,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
        );
      } else {
        DialogHelper.showMessage(
          context: context,
          message: result['message'] ?? 'Failed to update verification documents.',
          isError: true,
        );
      }
    }
  }

  Widget _buildImageUploadBox({
    required String title,
    required String? imagePath,
    required VoidCallback onTap,
    required VoidCallback onClear,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 14,
            color: AppTheme.darkColor,
          ),
        ),
        const SizedBox(height: 8),
        GestureDetector(
          onTap: imagePath == null ? onTap : null,
          child: Container(
            height: 150,
            decoration: BoxDecoration(
              color: Colors.grey.shade50,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.grey.shade200),
            ),
            child: imagePath == null
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.camera_alt_outlined, color: AppTheme.primaryColor, size: 36),
                        const SizedBox(height: 8),
                        Text(
                          'Tap to upload image',
                          style: TextStyle(color: Colors.grey.shade500, fontSize: 12),
                        ),
                      ],
                    ),
                  )
                : ClipRRect(
                    borderRadius: BorderRadius.circular(16),
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        Image.file(
                          File(imagePath),
                          fit: BoxFit.cover,
                        ),
                        Positioned(
                          right: 8,
                          top: 8,
                          child: CircleAvatar(
                            radius: 16,
                            backgroundColor: Colors.black54,
                            child: IconButton(
                              icon: const Icon(Icons.close, size: 16, color: Colors.white),
                              onPressed: onClear,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Update Verification',
          style: TextStyle(color: AppTheme.darkColor, fontWeight: FontWeight.bold, fontSize: 18),
        ),
        backgroundColor: Colors.white,
        elevation: 0.5,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new, color: AppTheme.darkColor, size: 18),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.amber.shade50,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.amber.shade200),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.info_outline, color: Colors.orange, size: 24),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Account Verification Status',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              color: AppTheme.darkColor,
                              fontSize: 14,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            'Please upload a valid SIM C card and face photo to change your status from unverified or riding class to verified.',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey.shade800,
                              height: 1.4,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              _buildImageUploadBox(
                title: 'Driver\'s License (SIM C) Photo *',
                imagePath: _licensePhotoPath,
                onTap: () => _pickImage('license'),
                onClear: () => setState(() => _licensePhotoPath = null),
              ),
              const SizedBox(height: 20),
              _buildImageUploadBox(
                title: 'Face Selfie Photo *',
                imagePath: _facePhotoPath,
                onTap: () => _pickImage('face'),
                onClear: () => setState(() => _facePhotoPath = null),
              ),
              const SizedBox(height: 40),
              SizedBox(
                height: 50,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _submitUpdate,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primaryColor,
                    foregroundColor: AppTheme.darkColor,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    elevation: 0,
                  ),
                  child: _isLoading
                      ? const Center(
                          child: SizedBox(
                            height: 24,
                            width: 24,
                            child: CircularProgressIndicator(
                              color: AppTheme.darkColor,
                              strokeWidth: 2.5,
                            ),
                          ),
                        )
                      : const Text(
                          'Submit Verification',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
