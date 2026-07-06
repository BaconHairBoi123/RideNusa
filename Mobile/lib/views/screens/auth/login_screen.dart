import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../../../core/app_theme.dart';
import '../../../core/dialog_helper.dart';
import '../../../REST-API/Services/auth_service.dart';

class LoginScreen extends StatefulWidget {
  final String? redirectTo;
  const LoginScreen({super.key, this.redirectTo});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  String _authMode = 'login'; // 'login', 'register', 'forgot_password'
  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;

  // Multi-step Registration and Verification state
  int _registerStep = 1;
  String _verificationType = 'sim'; // 'sim' or 'course'
  String? _licensePhotoPath;
  String? _facePhotoPath;
  final ImagePicker _imagePicker = ImagePicker();

  // Controllers for Login
  final _loginController = TextEditingController(); // email or username
  final _loginPasswordController = TextEditingController();

  // Controllers for Register
  final _registerNameController = TextEditingController();
  final _registerUsernameController = TextEditingController();
  final _registerEmailController = TextEditingController();
  final _registerPhoneController = TextEditingController();
  final _registerAddressController = TextEditingController();
  final _registerPasswordController = TextEditingController();
  final _registerConfirmPasswordController = TextEditingController();

  // Controllers and Keys for Forgot Password
  final _forgotEmailController = TextEditingController();
  final _forgotFormKey = GlobalKey<FormState>();

  // Global Keys for Forms
  final _loginFormKey = GlobalKey<FormState>();
  final _registerFormKey = GlobalKey<FormState>();

  void _submitLogin() async {
    if (!_loginFormKey.currentState!.validate()) return;

    // Close keyboard automatically
    FocusScope.of(context).unfocus();

    setState(() => _isLoading = true);

    final success = await AuthService().login(
      _loginController.text.trim(),
      _loginPasswordController.text,
    );

    setState(() => _isLoading = false);

    if (mounted) {
      if (success) {
        if (widget.redirectTo != null) {
          Navigator.pop(context, true);
        } else {
          Navigator.pushReplacementNamed(context, '/home');
        }
      } else {
        DialogHelper.showMessage(
          context: context,
          message: 'Login failed. Please check your credentials.',
          isError: true,
        );
      }
    }
  }

  void _submitRegister() async {
    if (_registerStep == 1) {
      if (!_registerFormKey.currentState!.validate()) return;
      setState(() {
        _registerStep = 2;
      });
      return;
    }

    // Validation for SIM upload
    if (_verificationType == 'sim') {
      if (_licensePhotoPath == null || _licensePhotoPath!.isEmpty) {
        DialogHelper.showMessage(
          context: context,
          message: "Please upload your Driver's License (SIM) photo to proceed.",
          isError: true,
        );
        return;
      }
      if (_facePhotoPath == null || _facePhotoPath!.isEmpty) {
        DialogHelper.showMessage(
          context: context,
          message: "Please upload your Face Selfie photo to proceed.",
          isError: true,
        );
        return;
      }
    }

    // Close keyboard automatically
    FocusScope.of(context).unfocus();

    setState(() => _isLoading = true);

    final result = await AuthService().register(
      name: _registerNameController.text.trim(),
      username: _registerUsernameController.text.trim(),
      email: _registerEmailController.text.trim(),
      password: _registerPasswordController.text,
      passwordConfirmation: _registerConfirmPasswordController.text,
      phoneNumber: _registerPhoneController.text.trim(),
      address: _registerAddressController.text.trim(),
      verificationType: _verificationType,
      licensePhotoPath: _licensePhotoPath,
      facePhotoPath: _facePhotoPath,
    );

    setState(() => _isLoading = false);

    if (mounted) {
      if (result['success'] == true) {
        if (widget.redirectTo != null) {
          Navigator.pop(context, true);
        } else {
          Navigator.pushReplacementNamed(context, '/home');
        }
      } else {
        String errorMsg = result['message'] ?? 'Registration failed.';
        if (result['errors'] != null && result['errors'] is Map) {
          final Map errors = result['errors'];
          final firstErrorList = errors.values.first;
          if (firstErrorList is List && firstErrorList.isNotEmpty) {
            errorMsg = firstErrorList.first.toString();
          }
        }
        DialogHelper.showMessage(
          context: context,
          message: errorMsg,
          isError: true,
        );
      }
    }
  }

  void _submitForgotPassword() async {
    if (!_forgotFormKey.currentState!.validate()) return;

    // Close keyboard automatically
    FocusScope.of(context).unfocus();

    setState(() => _isLoading = true);

    final result = await AuthService().forgotPassword(_forgotEmailController.text.trim());

    setState(() => _isLoading = false);

    if (mounted) {
      if (result['success'] == true) {
        DialogHelper.showMessage(
          context: context,
          message: result['message'] ?? 'Reset link has been sent to your email.',
          isError: false,
        );
        setState(() {
          _authMode = 'login';
        });
      } else {
        DialogHelper.showMessage(
          context: context,
          message: result['message'] ?? 'Failed to send reset link.',
          isError: true,
        );
      }
    }
  }

  @override
  void dispose() {
    _loginController.dispose();
    _loginPasswordController.dispose();
    _registerNameController.dispose();
    _registerUsernameController.dispose();
    _registerEmailController.dispose();
    _registerPhoneController.dispose();
    _registerAddressController.dispose();
    _registerPasswordController.dispose();
    _registerConfirmPasswordController.dispose();
    _forgotEmailController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              Color(0xFFFFC542), // Light yellow
              AppTheme.primaryColor, // Brand primary yellow (#FFB51D)
            ],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              // Header Controls (Back and Skip)
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8.0, vertical: 4.0),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Navigator.canPop(context)
                        ? IconButton(
                            icon: const Icon(Icons.arrow_back_ios_new, color: Colors.white, size: 20),
                            onPressed: () => Navigator.pop(context),
                          )
                        : const SizedBox(width: 48),
                    widget.redirectTo == null
                        ? TextButton(
                            onPressed: () {
                              Navigator.pushReplacementNamed(context, '/home');
                            },
                            child: const Text(
                              'Skip',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          )
                        : const SizedBox(width: 48),
                  ],
                ),
              ),

              // Logo & App Name Area
              Expanded(
                flex: 3,
                child: Center(
                  child: SingleChildScrollView(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        // ==========================================
                        // RIDE NUSA BRAND LOGO IMAGE
                        // Displays the white logo asset on the branded background
                        // ==========================================
                        Image.asset(
                          'assets/images/logo_ridenusa_white.png',
                          width: 140,
                          height: 140,
                          fit: BoxFit.contain,
                        ),
                      ],
                    ),
                  ),
                ),
              ),

              // Forms Area (Bottom Card)
              Expanded(
                flex: 7,
                child: Container(
                  decoration: const BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.only(
                      topLeft: Radius.circular(32),
                      topRight: Radius.circular(32),
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black12,
                        blurRadius: 15,
                        offset: Offset(0, -5),
                      )
                    ],
                  ),
                  child: ClipRRect(
                    borderRadius: const BorderRadius.only(
                      topLeft: Radius.circular(32),
                      topRight: Radius.circular(32),
                    ),
                    child: SingleChildScrollView(
                      physics: const ClampingScrollPhysics(),
                      padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 32.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          if (widget.redirectTo != null) ...[
                            Container(
                              margin: const EdgeInsets.only(bottom: 20),
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: AppTheme.primaryColor.withOpacity(0.1),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: AppTheme.primaryColor.withOpacity(0.3)),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.info_outline, color: AppTheme.darkColor),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      'Please sign in to proceed with renting your bike.',
                                      style: TextStyle(
                                        color: AppTheme.darkColor.withOpacity(0.8),
                                        fontSize: 13,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],

                          // Smooth Animated transition of content
                          AnimatedSize(
                            duration: const Duration(milliseconds: 300),
                            curve: Curves.easeInOut,
                            child: AnimatedSwitcher(
                              duration: const Duration(milliseconds: 400),
                              switchInCurve: Curves.easeIn,
                              switchOutCurve: Curves.easeOut,
                              transitionBuilder: (Widget child, Animation<double> animation) {
                                return FadeTransition(
                                  opacity: animation,
                                  child: SlideTransition(
                                    position: Tween<Offset>(
                                      begin: const Offset(0.0, 0.1),
                                      end: Offset.zero,
                                    ).animate(animation),
                                    child: child,
                                  ),
                                );
                              },
                              child: _authMode == 'login'
                                  ? _buildLoginForm()
                                  : _authMode == 'register'
                                      ? _buildRegisterForm()
                                      : _buildForgotPasswordForm(),
                            ),
                          ),

                           const SizedBox(height: 12),

                          // Toggle Auth Screen Mode
                          Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                                _authMode == 'forgot_password'
                                    ? "Remembered your password? "
                                    : _authMode == 'login'
                                        ? "Don't have an account yet? "
                                        : "Already have an account? ",
                                style: const TextStyle(color: Colors.grey, fontSize: 13),
                              ),
                              TextButton(
                                onPressed: () {
                                  setState(() {
                                    if (_authMode == 'forgot_password' || _authMode == 'register') {
                                      _authMode = 'login';
                                    } else {
                                      _authMode = 'register';
                                    }
                                    _registerStep = 1;
                                  });
                                },
                                child: Text(
                                  _authMode == 'login' ? 'Sign Up' : 'Log In',
                                  style: const TextStyle(
                                    color: AppTheme.primaryColor,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 13,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
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

  // --- LOGIN FORM WIDGET ---
  Widget _buildLoginForm() {
    return Form(
      key: _loginFormKey,
      child: Column(
        key: const ValueKey('login_form'),
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text(
            'Welcome Back',
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: AppTheme.darkColor,
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            'Log in to access your motorcycle rentals',
            style: TextStyle(fontSize: 13, color: Colors.grey),
          ),
          const SizedBox(height: 24),

          // Email/Username Input
          TextFormField(
            controller: _loginController,
            decoration: InputDecoration(
              hintText: 'Email or Username',
              prefixIcon: const Icon(Icons.person_outline, size: 20),
              filled: true,
              fillColor: Colors.grey.shade50,
              contentPadding: const EdgeInsets.symmetric(vertical: 16),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: Colors.grey.shade200),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: Colors.grey.shade200),
              ),
            ),
            validator: (value) {
              if (value == null || value.trim().isEmpty) {
                return 'Please enter your email or username';
              }
              return null;
            },
          ),
          const SizedBox(height: 16),

          // Password Input
          TextFormField(
            controller: _loginPasswordController,
            obscureText: _obscurePassword,
            decoration: InputDecoration(
              hintText: 'Password',
              prefixIcon: const Icon(Icons.lock_outline, size: 20),
              suffixIcon: IconButton(
                icon: Icon(
                  _obscurePassword ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                  size: 20,
                ),
                onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
              ),
              filled: true,
              fillColor: Colors.grey.shade50,
              contentPadding: const EdgeInsets.symmetric(vertical: 16),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: Colors.grey.shade200),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: Colors.grey.shade200),
              ),
            ),
            validator: (value) {
              if (value == null || value.isEmpty) {
                return 'Please enter your password';
              }
              return null;
            },
          ),
          const SizedBox(height: 12),

          // Forgot Password
          Align(
            alignment: Alignment.centerRight,
            child: TextButton(
              onPressed: () {
                setState(() {
                  _authMode = 'forgot_password';
                });
              },
              child: const Text(
                'Forgot Password?',
                style: TextStyle(color: Colors.grey, fontSize: 12),
              ),
            ),
          ),
          const SizedBox(height: 12),

          // Sign In Button
          SizedBox(
            height: 50,
            child: ElevatedButton(
              onPressed: _isLoading ? null : _submitLogin,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primaryColor,
                padding: EdgeInsets.zero,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
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
                      'Log In',
                      style: TextStyle(
                        color: AppTheme.darkColor,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
            ),
          ),
        ],
      ),
    );
  }

  // --- FORGOT PASSWORD FORM WIDGET ---
  Widget _buildForgotPasswordForm() {
    return Form(
      key: _forgotFormKey,
      child: Column(
        key: const ValueKey('forgot_password_form'),
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text(
            'Reset Password',
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: AppTheme.darkColor,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Enter your registered email address and we will send you instructions to reset your password.',
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey,
              height: 1.3,
            ),
          ),
          const SizedBox(height: 20),



          // Email Input
          TextFormField(
            controller: _forgotEmailController,
            keyboardType: TextInputType.emailAddress,
            decoration: _getInputDecoration('Email Address', Icons.email_outlined),
            validator: (value) {
              if (value == null || value.trim().isEmpty) {
                return 'Please enter your email';
              }
              if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(value.trim())) {
                return 'Please enter a valid email address';
              }
              return null;
            },
          ),
          const SizedBox(height: 24),

          // Submit Reset Button
          SizedBox(
            height: 50,
            child: ElevatedButton(
              onPressed: _isLoading ? null : _submitForgotPassword,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primaryColor,
                padding: EdgeInsets.zero,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
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
                      'Send Reset Link',
                      style: TextStyle(
                        color: AppTheme.darkColor,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
            ),
          ),
        ],
      ),
    );
  }

  // Helper to pick images
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

  // --- REGISTER FORM WIDGET ---
  Widget _buildRegisterForm() {
    return Form(
      key: _registerFormKey,
      child: Column(
        key: const ValueKey('register_form'),
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              if (_registerStep == 2)
                IconButton(
                  icon: const Icon(Icons.arrow_back, color: AppTheme.darkColor),
                  onPressed: () {
                    setState(() {
                      _registerStep = 1;
                    });
                  },
                ),
              Expanded(
                child: Text(
                  _registerStep == 1 ? 'Create Account' : 'Verification',
                  style: const TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.darkColor,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            _registerStep == 1
                ? 'Step 1: Fill in your account details'
                : 'Step 2: Upload Driver\'s License or choose Course',
            style: const TextStyle(fontSize: 13, color: Colors.grey),
          ),
          const SizedBox(height: 12),
          
          // Step progress indicator
          Row(
            children: [
              Expanded(
                child: Container(
                  height: 4,
                  decoration: BoxDecoration(
                    color: AppTheme.primaryColor,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Container(
                  height: 4,
                  decoration: BoxDecoration(
                    color: _registerStep == 2 ? AppTheme.primaryColor : Colors.grey.shade200,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Render Step 1 or Step 2
          _registerStep == 1 ? _buildStep1Fields() : _buildStep2Fields(),
        ],
      ),
    );
  }

  // STEP 1 Fields (Basic Information)
  Widget _buildStep1Fields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Full Name Input
        TextFormField(
          controller: _registerNameController,
          decoration: _getInputDecoration('Full Name', Icons.badge_outlined),
          validator: (value) {
            if (value == null || value.trim().isEmpty) return 'Full Name is required';
            return null;
          },
        ),
        const SizedBox(height: 14),

        // Username Input
        TextFormField(
          controller: _registerUsernameController,
          decoration: _getInputDecoration('Username', Icons.alternate_email),
          validator: (value) {
            if (value == null || value.trim().isEmpty) return 'Username is required';
            return null;
          },
        ),
        const SizedBox(height: 14),

        // Email Input
        TextFormField(
          controller: _registerEmailController,
          keyboardType: TextInputType.emailAddress,
          decoration: _getInputDecoration('Email Address', Icons.email_outlined),
          validator: (value) {
            if (value == null || value.trim().isEmpty) return 'Email is required';
            if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(value.trim())) {
              return 'Enter a valid email address';
            }
            return null;
          },
        ),
        const SizedBox(height: 14),

        // Phone Number Input
        TextFormField(
          controller: _registerPhoneController,
          keyboardType: TextInputType.phone,
          decoration: _getInputDecoration('Phone Number', Icons.phone_outlined),
          validator: (value) {
            if (value == null || value.trim().isEmpty) return 'Phone number is required';
            return null;
          },
        ),
        const SizedBox(height: 14),

        // Address Input
        TextFormField(
          controller: _registerAddressController,
          maxLines: 2,
          decoration: _getInputDecoration('Address', Icons.home_outlined),
          validator: (value) {
            if (value == null || value.trim().isEmpty) return 'Address is required';
            return null;
          },
        ),
        const SizedBox(height: 14),

        // Password Input
        TextFormField(
          controller: _registerPasswordController,
          obscureText: _obscurePassword,
          decoration: InputDecoration(
            hintText: 'Password',
            prefixIcon: const Icon(Icons.lock_outline, size: 20),
            suffixIcon: IconButton(
              icon: Icon(
                _obscurePassword ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                size: 20,
              ),
              onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
            ),
            filled: true,
            fillColor: Colors.grey.shade50,
            contentPadding: const EdgeInsets.symmetric(vertical: 16),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: Colors.grey.shade200),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: Colors.grey.shade200),
            ),
          ),
          validator: (value) {
            if (value == null || value.isEmpty) return 'Password is required';
            if (value.length < 8) return 'Password must be at least 8 characters';
            return null;
          },
        ),
        const SizedBox(height: 14),

        // Confirm Password Input
        TextFormField(
          controller: _registerConfirmPasswordController,
          obscureText: _obscureConfirmPassword,
          decoration: InputDecoration(
            hintText: 'Confirm Password',
            prefixIcon: const Icon(Icons.lock_outline, size: 20),
            suffixIcon: IconButton(
              icon: Icon(
                _obscureConfirmPassword ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                size: 20,
              ),
              onPressed: () => setState(() => _obscureConfirmPassword = !_obscureConfirmPassword),
            ),
            filled: true,
            fillColor: Colors.grey.shade50,
            contentPadding: const EdgeInsets.symmetric(vertical: 16),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: Colors.grey.shade200),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: Colors.grey.shade200),
            ),
          ),
          validator: (value) {
            if (value == null || value.isEmpty) return 'Confirm password is required';
            if (value != _registerPasswordController.text) {
              return 'Passwords do not match';
            }
            return null;
          },
        ),
        const SizedBox(height: 24),

        // Next Button
        SizedBox(
          height: 50,
          child: ElevatedButton(
            onPressed: _submitRegister,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryColor,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
            child: const Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  'Continue to Verification',
                  style: TextStyle(
                    color: AppTheme.darkColor,
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                SizedBox(width: 8),
                Icon(Icons.arrow_forward, color: AppTheme.darkColor, size: 18),
              ],
            ),
          ),
        ),
      ],
    );
  }

  // STEP 2 Fields (Verification Upload SIM / Option Riding Course)
  Widget _buildStep2Fields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const Text(
          'Verification Method',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppTheme.darkColor),
        ),
        const SizedBox(height: 12),

        // Method Choice Chips / Cards
        _buildSelectionCard(
          type: 'sim',
          title: 'I have a Driver\'s License (SIM)',
          subtitle: 'Upload a picture of your SIM C card for instant verification.',
          icon: Icons.badge_outlined,
        ),
        const SizedBox(height: 12),
        _buildSelectionCard(
          type: 'course',
          title: 'No SIM? Join Riding Course',
          subtitle: 'Enroll in RideNusa local riding class to get safe certified.',
          icon: Icons.school_outlined,
        ),
        const SizedBox(height: 24),

        // Conditional Verification Form Fields
        if (_verificationType == 'sim') ...[
          // SIM Upload Box
          _buildImageUploadBox(
            title: 'Driver\'s License (SIM C) Photo *',
            imagePath: _licensePhotoPath,
            onTap: () => _pickImage('license'),
            onClear: () => setState(() => _licensePhotoPath = null),
          ),
          const SizedBox(height: 16),

          // Face Upload Box (Required)
          _buildImageUploadBox(
            title: 'Face Selfie Photo *',
            imagePath: _facePhotoPath,
            onTap: () => _pickImage('face'),
            onClear: () => setState(() => _facePhotoPath = null),
          ),
        ] else ...[
          // Riding Course Friendly Alert
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.amber.shade50,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.amber.shade200),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Row(
                  children: [
                    Icon(Icons.info_outline, color: Colors.orange),
                    SizedBox(width: 8),
                    Text(
                      'Riding Course Program',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        color: AppTheme.darkColor,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  'No motorcycle license? No worries! Choose this and our RideNusa trainer team will schedule a short driving class before handing you the keys.',
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

        const SizedBox(height: 32),

        // Submit Sign Up Button
        SizedBox(
          height: 50,
          child: ElevatedButton(
            onPressed: _isLoading ? null : _submitRegister,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryColor,
              padding: EdgeInsets.zero,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
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
                    'Complete Sign Up',
                    style: TextStyle(
                      color: AppTheme.darkColor,
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
          ),
        ),
      ],
    );
  }

  // Helper Custom Selection Card
  Widget _buildSelectionCard({
    required String type,
    required String title,
    required String subtitle,
    required IconData icon,
  }) {
    final isSelected = _verificationType == type;
    return GestureDetector(
      onTap: () {
        setState(() {
          _verificationType = type;
        });
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isSelected ? AppTheme.primaryColor.withOpacity(0.05) : Colors.grey.shade50,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isSelected ? AppTheme.primaryColor : Colors.grey.shade200,
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: isSelected ? AppTheme.primaryColor.withOpacity(0.1) : Colors.grey.shade100,
                shape: BoxShape.circle,
              ),
              child: Icon(
                icon,
                color: isSelected ? AppTheme.primaryColor : Colors.grey,
                size: 24,
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 13,
                      color: isSelected ? AppTheme.darkColor : Colors.black87,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: TextStyle(
                      fontSize: 11,
                      color: Colors.grey.shade600,
                    ),
                  ),
                ],
              ),
            ),
            Icon(
              isSelected ? Icons.radio_button_checked : Icons.radio_button_off,
              color: isSelected ? AppTheme.primaryColor : Colors.grey,
            ),
          ],
        ),
      ),
    );
  }

  // Helper Image Box Upload Widget
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
            fontSize: 13,
            color: AppTheme.darkColor,
          ),
        ),
        const SizedBox(height: 8),
        GestureDetector(
          onTap: imagePath == null ? onTap : null,
          child: Container(
            height: 120,
            decoration: BoxDecoration(
              color: Colors.grey.shade50,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.grey.shade200),
            ),
            child: imagePath == null
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.camera_alt_outlined, color: Colors.grey.shade400, size: 32),
                        const SizedBox(height: 6),
                        Text(
                          'Tap to upload image',
                          style: TextStyle(color: Colors.grey.shade500, fontSize: 12),
                        ),
                      ],
                    ),
                  )
                : ClipRRect(
                    borderRadius: BorderRadius.circular(12),
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

  // Helper Input Decoration
  InputDecoration _getInputDecoration(String hint, IconData icon) {
    return InputDecoration(
      hintText: hint,
      prefixIcon: Icon(icon, size: 20),
      filled: true,
      fillColor: Colors.grey.shade50,
      contentPadding: const EdgeInsets.symmetric(vertical: 16),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.grey.shade200),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.grey.shade200),
      ),
    );
  }
}
