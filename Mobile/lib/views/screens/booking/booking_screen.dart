import 'dart:async';
import 'dart:convert';
import 'dart:math';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:http/http.dart' as http;
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/app_theme.dart';
import '../../../core/dialog_helper.dart';
import '../../../REST-API/Models/motorcycle.dart';
import '../../../REST-API/Models/accessory.dart';
import '../../../REST-API/Services/booking_service.dart';
import 'cart_screen.dart';

class BookingScreen extends StatefulWidget {
  final Motorcycle motorcycle;

  const BookingScreen({super.key, required this.motorcycle});

  @override
  State<BookingScreen> createState() => _BookingScreenState();
}

class _BookingScreenState extends State<BookingScreen> {
  final _formKey = GlobalKey<FormState>();
  final _bookingService = BookingService();

  static const double _storeLat = -8.798599;
  static const double _storeLng = 115.162452;

  DateTime _startDate = DateTime.now();
  DateTime _endDate = DateTime.now().add(const Duration(days: 1));
  
  String _deliveryType = 'pickup'; // 'pickup' or 'delivery'
  final _addressController = TextEditingController();
  double _distanceKm = 0.0; // Distance in KM calculated from address
  double? _destLat;
  double? _destLng;

  Timer? _debounce;
  bool _searchingAddress = false;
  List<dynamic> _suggestions = [];
  
  List<Accessory> _allAccessories = [];
  final List<int> _selectedAccessoryIds = [];
  bool _isLoadingAccessories = true;
  bool _isSubmitting = false;

  late final PageController _pageController;
  int _currentImageIndex = 0;

  @override
  void initState() {
    super.initState();
    _pageController = PageController();
    _loadAccessories();
  }

  @override
  void dispose() {
    _addressController.dispose();
    _debounce?.cancel();
    _pageController.dispose();
    super.dispose();
  }

  double _calculateDistance(double lat1, double lon1, double lat2, double lon2) {
    const r = 6371; // Earth radius in km
    final dLat = (lat2 - lat1) * pi / 180;
    final dLon = (lon2 - lon1) * pi / 180;
    final a = sin(dLat / 2) * sin(dLat / 2) +
        cos(lat1 * pi / 180) * cos(lat2 * pi / 180) * sin(dLon / 2) * sin(dLon / 2);
    final c = 2 * atan2(sqrt(a), sqrt(1 - a));
    return r * c;
  }

  void _onAddressChanged(String query) {
    if (_debounce?.isActive ?? false) _debounce!.cancel();
    _debounce = Timer(const Duration(milliseconds: 500), () {
      _searchAddress(query);
    });
  }

  Future<void> _searchAddress(String query) async {
    if (query.trim().length < 3) {
      setState(() {
        _suggestions = [];
      });
      return;
    }

    setState(() {
      _searchingAddress = true;
    });

    try {
      final url = Uri.parse(
        'https://nominatim.openstreetmap.org/search?format=json&q=${Uri.encodeComponent(query)}&viewbox=114.4,-8.1,115.7,-8.9&bounded=1',
      );
      final response = await http.get(
        url,
        headers: {'User-Agent': 'RideNusa-Mobile-App'},
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = json.decode(response.body);
        setState(() {
          _suggestions = data;
          _searchingAddress = false;
        });
      } else {
        setState(() {
          _searchingAddress = false;
        });
      }
    } catch (_) {
      setState(() {
        _searchingAddress = false;
      });
    }
  }

  Future<void> _loadAccessories() async {
    try {
      final list = await _bookingService.getAccessories();
      setState(() {
        _allAccessories = list;
        _isLoadingAccessories = false;
      });
    } catch (_) {
      setState(() {
        _isLoadingAccessories = false;
      });
    }
  }

  int get _rentalDays {
    final diff = _endDate.difference(_startDate).inDays;
    return diff < 0 ? 0 : diff + 1;
  }

  int get _basePriceTotal {
    return widget.motorcycle.price * _rentalDays;
  }

  int get _accessoriesPriceTotal {
    int total = 0;
    for (var acc in _allAccessories) {
      if (_selectedAccessoryIds.contains(acc.id)) {
        total += (acc.dailyPrice * _rentalDays);
      }
    }
    return total;
  }

  int get _deliveryFeeTotal {
    if (_deliveryType == 'pickup') return 0;
    return (_distanceKm.ceil() * 5000);
  }

  int get _grandTotal {
    return _basePriceTotal + _accessoriesPriceTotal + _deliveryFeeTotal;
  }

  Future<void> _selectStartDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _startDate,
      firstDate: DateTime.now().subtract(const Duration(days: 1)),
      lastDate: DateTime.now().add(const Duration(days: 90)),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: AppTheme.primaryColor,
              onPrimary: AppTheme.darkColor,
              onSurface: AppTheme.darkColor,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null && picked != _startDate) {
      setState(() {
        _startDate = picked;
        if (_endDate.isBefore(_startDate)) {
          _endDate = _startDate.add(const Duration(days: 1));
        }
      });
    }
  }

  Future<void> _selectEndDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _endDate,
      firstDate: _startDate,
      lastDate: _startDate.add(const Duration(days: 90)),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: AppTheme.primaryColor,
              onPrimary: AppTheme.darkColor,
              onSurface: AppTheme.darkColor,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null && picked != _endDate) {
      setState(() {
        _endDate = picked;
      });
    }
  }

  Future<void> _submitBooking({bool payNow = true}) async {
    if (_deliveryType == 'delivery' && !_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _isSubmitting = true);

    final formatter = DateFormat('yyyy-MM-dd');
    final startDateStr = formatter.format(_startDate);
    final endDateStr = formatter.format(_endDate);

    final response = await _bookingService.createBooking(
      motorcycleId: widget.motorcycle.id,
      startDate: startDateStr,
      endDate: endDateStr,
      deliveryType: _deliveryType,
      distanceKm: _deliveryType == 'delivery' ? _distanceKm : null,
      latitude: _deliveryType == 'delivery' ? (_destLat ?? -8.6500) : null,
      longitude: _deliveryType == 'delivery' ? (_destLng ?? 115.2167) : null,
      deliveryAddress: _deliveryType == 'delivery' ? _addressController.text.trim() : null,
      accessories: _selectedAccessoryIds,
    );

    setState(() => _isSubmitting = false);

    if (mounted) {
      if (response['success'] == true) {
        if (payNow) {
          final snapToken = response['data']['snap_token'];
          final paymentUrl = 'https://app.sandbox.midtrans.com/snap/v2/vtweb/$snapToken';
          final Uri url = Uri.parse(paymentUrl);
          if (await canLaunchUrl(url)) {
            await launchUrl(url, mode: LaunchMode.inAppWebView);
          }
        } else {
          DialogHelper.showMessage(
            context: context,
            message: 'Motorcycle added to cart successfully. You can pay later.',
          );
        }
        if (mounted) {
          Navigator.pushReplacement(
            context,
            AppTheme.animatedRoute(const CartScreen()),
          );
        }
      } else {
        showDialog(
          context: context,
          builder: (dialogCtx) => AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: const Row(
              children: [
                Icon(Icons.error_outline, color: Colors.redAccent, size: 28),
                SizedBox(width: 8),
                Text('Booking Failed'),
              ],
            ),
            content: Text(response['message'] ?? 'An error occurred during booking.'),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(dialogCtx),
                child: const Text('OK'),
              ),
            ],
          ),
        );
      }
    }
  }

  void _showImagePreview(String imageUrl) {
    showDialog(
      context: context,
      barrierDismissible: true,
      builder: (BuildContext context) {
        return Dialog(
          backgroundColor: Colors.transparent,
          insetPadding: const EdgeInsets.all(12),
          child: Stack(
            alignment: Alignment.center,
            children: [
              InteractiveViewer(
                minScale: 0.5,
                maxScale: 4.0,
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(16),
                  child: imageUrl.isNotEmpty
                      ? CachedNetworkImage(
                          imageUrl: imageUrl,
                          fit: BoxFit.contain,
                          placeholder: (context, url) => const Center(
                            child: CircularProgressIndicator(
                              valueColor: AlwaysStoppedAnimation<Color>(AppTheme.primaryColor),
                            ),
                          ),
                          errorWidget: (context, url, error) =>
                              const Icon(Icons.two_wheeler, size: 120, color: Colors.white),
                        )
                      : const Icon(Icons.two_wheeler, size: 120, color: Colors.white),
                ),
              ),
              Positioned(
                top: 8,
                right: 8,
                child: CircleAvatar(
                  backgroundColor: Colors.black.withOpacity(0.5),
                  child: IconButton(
                    icon: const Icon(Icons.close, color: Colors.white),
                    onPressed: () => Navigator.pop(context),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final currencyFormat = NumberFormat.decimalPattern('id');

    return Scaffold(
      appBar: AppBar(
        title: const Text('Book Motorcycle', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: AppTheme.darkColor)),
        backgroundColor: Colors.white,
        elevation: 0.5,
        iconTheme: const IconThemeData(color: AppTheme.darkColor),
      ),
      body: _isSubmitting
          ? const Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircularProgressIndicator(valueColor: AlwaysStoppedAnimation<Color>(AppTheme.primaryColor)),
                  SizedBox(height: 16),
                  Text('Processing your booking, please wait...', style: TextStyle(fontWeight: FontWeight.w500)),
                ],
              ),
            )
          : Form(
              key: _formKey,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Motorcycle Swipeable Gallery
                    if (widget.motorcycle.gallery.isNotEmpty) ...[
                      Card(
                        elevation: 0,
                        color: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                          side: BorderSide(color: Colors.grey.shade200),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.all(8.0),
                          child: Stack(
                            alignment: Alignment.bottomCenter,
                            children: [
                              SizedBox(
                                height: 200,
                                child: PageView.builder(
                                  controller: _pageController,
                                  itemCount: widget.motorcycle.gallery.length,
                                  onPageChanged: (index) {
                                    setState(() {
                                      _currentImageIndex = index;
                                    });
                                  },
                                  itemBuilder: (context, index) {
                                    final imgUrl = widget.motorcycle.gallery[index];
                                    return GestureDetector(
                                      onTap: () => _showImagePreview(imgUrl),
                                      child: ClipRRect(
                                        borderRadius: BorderRadius.circular(12),
                                        child: Container(
                                          color: AppTheme.backgroundColor,
                                          child: imgUrl.isNotEmpty
                                              ? CachedNetworkImage(
                                                  imageUrl: imgUrl,
                                                  fit: BoxFit.cover,
                                                  placeholder: (context, url) => const Center(
                                                    child: CircularProgressIndicator(
                                                      valueColor: AlwaysStoppedAnimation<Color>(AppTheme.primaryColor),
                                                    ),
                                                  ),
                                                  errorWidget: (context, url, error) =>
                                                      const Icon(Icons.two_wheeler, size: 64, color: Colors.grey),
                                                )
                                              : const Icon(Icons.two_wheeler, size: 64, color: Colors.grey),
                                        ),
                                      ),
                                    );
                                  },
                                ),
                              ),
                              if (widget.motorcycle.gallery.length > 1)
                                Positioned(
                                  bottom: 12,
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: Colors.black.withOpacity(0.3),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Row(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: List.generate(
                                        widget.motorcycle.gallery.length,
                                        (index) => AnimatedContainer(
                                          duration: const Duration(milliseconds: 250),
                                          margin: const EdgeInsets.symmetric(horizontal: 3),
                                          height: 6,
                                          width: _currentImageIndex == index ? 16 : 6,
                                          decoration: BoxDecoration(
                                            color: _currentImageIndex == index
                                                ? AppTheme.primaryColor
                                                : Colors.white,
                                            borderRadius: BorderRadius.circular(3),
                                          ),
                                        ),
                                      ),
                                    ),
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],

                    // Motorcycle Summary Card
                    Card(
                      elevation: 0,
                      color: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                        side: BorderSide(color: Colors.grey.shade200),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(12.0),
                        child: Row(
                          children: [
                            GestureDetector(
                              onTap: () {
                                if (widget.motorcycle.imageUrl != null) {
                                  _showImagePreview(widget.motorcycle.imageUrl!);
                                }
                              },
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(12),
                                child: Container(
                                  color: AppTheme.backgroundColor,
                                  width: 90,
                                  height: 90,
                                  child: widget.motorcycle.imageUrl != null
                                      ? CachedNetworkImage(
                                          imageUrl: widget.motorcycle.imageUrl!,
                                          fit: BoxFit.contain,
                                          placeholder: (context, url) => const Center(
                                            child: CircularProgressIndicator(
                                              valueColor: AlwaysStoppedAnimation<Color>(AppTheme.primaryColor),
                                            ),
                                          ),
                                          errorWidget: (context, url, error) =>
                                              const Icon(Icons.motorcycle, size: 40, color: Colors.grey),
                                        )
                                      : const Icon(Icons.motorcycle, size: 40, color: Colors.grey),
                                ),
                              ),
                            ),
                            const SizedBox(width: 16),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    '${widget.motorcycle.brand} - ${widget.motorcycle.type}',
                                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppTheme.darkColor),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    '${widget.motorcycle.category} • ${widget.motorcycle.cc}cc',
                                    style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                                  ),
                                  const SizedBox(height: 8),
                                  Text(
                                    'Rp ${currencyFormat.format(widget.motorcycle.price)} / day',
                                    style: const TextStyle(
                                      color: AppTheme.primaryColor,
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    
                    // Motorcycle Specifications / Info Row
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        // CC Specification
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: Colors.grey.shade200),
                            ),
                            child: Column(
                              children: [
                                const Icon(Icons.speed, color: AppTheme.primaryColor, size: 20),
                                const SizedBox(height: 6),
                                Text(
                                  'Engine',
                                  style: TextStyle(fontSize: 10, color: Colors.grey.shade500, fontWeight: FontWeight.w500),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  '${widget.motorcycle.cc} cc',
                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.darkColor),
                                  textAlign: TextAlign.center,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        // Transmission Specification
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: Colors.grey.shade200),
                            ),
                            child: Column(
                              children: [
                                const Icon(Icons.settings_input_component, color: AppTheme.primaryColor, size: 20),
                                const SizedBox(height: 6),
                                Text(
                                  'Transmission',
                                  style: TextStyle(fontSize: 10, color: Colors.grey.shade500, fontWeight: FontWeight.w500),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  widget.motorcycle.transmission,
                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.darkColor),
                                  textAlign: TextAlign.center,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        // Fuel Specification
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: Colors.grey.shade200),
                            ),
                            child: Column(
                              children: [
                                const Icon(Icons.local_gas_station, color: AppTheme.primaryColor, size: 20),
                                const SizedBox(height: 6),
                                Text(
                                  'Fuel',
                                  style: TextStyle(fontSize: 10, color: Colors.grey.shade500, fontWeight: FontWeight.w500),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  widget.motorcycle.fuelConfiguration.isNotEmpty 
                                      ? widget.motorcycle.fuelConfiguration
                                      : 'Petrol',
                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.darkColor),
                                  textAlign: TextAlign.center,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),

                    // Motorcycle Description Section
                    const SizedBox(height: 16),
                    const Text('Motorcycle Description', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppTheme.darkColor)),
                    const SizedBox(height: 8),
                    Card(
                      elevation: 0,
                      color: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                        side: BorderSide(color: Colors.grey.shade200),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Text(
                          (widget.motorcycle.description != null && widget.motorcycle.description!.isNotEmpty)
                              ? widget.motorcycle.description!
                              : 'This premium ${widget.motorcycle.brand} ${widget.motorcycle.type} has been fully serviced and is ready for your ride. Features advanced handling, excellent fuel efficiency, and a comfortable ride posture.',
                          style: TextStyle(color: Colors.grey.shade700, fontSize: 13, height: 1.5),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Date Selection Card
                    const Text('Rental Dates', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppTheme.darkColor)),
                    const SizedBox(height: 8),
                    Card(
                      elevation: 0,
                      color: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                        side: BorderSide(color: Colors.grey.shade200),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Column(
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: InkWell(
                                    onTap: _selectStartDate,
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text('START DATE', style: TextStyle(color: Colors.grey.shade600, fontSize: 10, fontWeight: FontWeight.bold)),
                                        const SizedBox(height: 6),
                                        Row(
                                          children: [
                                            const Icon(Icons.calendar_today, size: 16, color: AppTheme.primaryColor),
                                            const SizedBox(width: 8),
                                            Text(DateFormat('dd MMM yyyy').format(_startDate), style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                                Container(height: 30, width: 1, color: Colors.grey.shade300),
                                const SizedBox(width: 16),
                                Expanded(
                                  child: InkWell(
                                    onTap: _selectEndDate,
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text('END DATE', style: TextStyle(color: Colors.grey.shade600, fontSize: 10, fontWeight: FontWeight.bold)),
                                        const SizedBox(height: 6),
                                        Row(
                                          children: [
                                            const Icon(Icons.calendar_today, size: 16, color: AppTheme.primaryColor),
                                            const SizedBox(width: 8),
                                            Text(DateFormat('dd MMM yyyy').format(_endDate), style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const Divider(height: 24),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                const Text('Total Duration', style: TextStyle(fontWeight: FontWeight.w500, fontSize: 13)),
                                Text('$_rentalDays Days', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppTheme.primaryColor)),
                              ],
                            )
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Delivery Options
                    const Text('Delivery Type', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppTheme.darkColor)),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Expanded(
                          child: GestureDetector(
                            onTap: () => setState(() => _deliveryType = 'pickup'),
                            child: Container(
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              decoration: BoxDecoration(
                                color: _deliveryType == 'pickup' ? AppTheme.primaryColor.withOpacity(0.15) : Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(
                                  color: _deliveryType == 'pickup' ? AppTheme.primaryColor : Colors.grey.shade300,
                                  width: 1.5,
                                ),
                              ),
                              child: Column(
                                children: [
                                  Icon(Icons.store, color: _deliveryType == 'pickup' ? AppTheme.darkColor : Colors.grey.shade600),
                                  const SizedBox(height: 4),
                                  Text('Store Pickup', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: _deliveryType == 'pickup' ? AppTheme.darkColor : Colors.grey.shade700)),
                                ],
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: GestureDetector(
                            onTap: () => setState(() => _deliveryType = 'delivery'),
                            child: Container(
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              decoration: BoxDecoration(
                                color: _deliveryType == 'delivery' ? AppTheme.primaryColor.withOpacity(0.15) : Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(
                                  color: _deliveryType == 'delivery' ? AppTheme.primaryColor : Colors.grey.shade300,
                                  width: 1.5,
                                ),
                              ),
                              child: Column(
                                children: [
                                  Icon(Icons.local_shipping, color: _deliveryType == 'delivery' ? AppTheme.darkColor : Colors.grey.shade600),
                                  const SizedBox(height: 4),
                                  Text('Home Delivery', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: _deliveryType == 'delivery' ? AppTheme.darkColor : Colors.grey.shade700)),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Delivery Address Form
                    if (_deliveryType == 'delivery') ...[
                      Card(
                        elevation: 0,
                        color: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                          side: BorderSide(color: Colors.grey.shade200),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Delivery Address', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                              const SizedBox(height: 8),
                              TextFormField(
                                controller: _addressController,
                                onChanged: _onAddressChanged,
                                decoration: InputDecoration(
                                  hintText: 'Type address (e.g. Kuta, Denpasar)...',
                                  hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 13),
                                  filled: true,
                                  fillColor: AppTheme.backgroundColor,
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
                                  suffixIcon: _searchingAddress
                                      ? const SizedBox(
                                          width: 20,
                                          height: 20,
                                          child: Padding(
                                            padding: EdgeInsets.all(12),
                                            child: CircularProgressIndicator(strokeWidth: 2, valueColor: AlwaysStoppedAnimation<Color>(AppTheme.primaryColor)),
                                          ),
                                        )
                                      : null,
                                ),
                                style: const TextStyle(fontSize: 13),
                                validator: (val) => val == null || val.isEmpty ? 'Please enter delivery address' : null,
                              ),
                              if (_suggestions.isNotEmpty) ...[
                                const SizedBox(height: 8),
                                Container(
                                  constraints: const BoxConstraints(maxHeight: 180),
                                  decoration: BoxDecoration(
                                    color: Colors.white,
                                    borderRadius: BorderRadius.circular(8),
                                    border: Border.all(color: Colors.grey.shade200),
                                  ),
                                  child: ListView.builder(
                                    shrinkWrap: true,
                                    physics: const ClampingScrollPhysics(),
                                    itemCount: _suggestions.length,
                                    itemBuilder: (context, idx) {
                                      final item = _suggestions[idx];
                                      return ListTile(
                                        dense: true,
                                        leading: const Icon(Icons.location_on, size: 16, color: AppTheme.primaryColor),
                                        title: Text(item['display_name'] ?? '', style: const TextStyle(fontSize: 12)),
                                        onTap: () {
                                          final double lat = double.tryParse(item['lat']?.toString() ?? '') ?? 0.0;
                                          final double lon = double.tryParse(item['lon']?.toString() ?? '') ?? 0.0;
                                          final dist = _calculateDistance(_storeLat, _storeLng, lat, lon);
                                          
                                          setState(() {
                                            _addressController.text = item['display_name'] ?? '';
                                            _destLat = lat;
                                            _destLng = lon;
                                            _distanceKm = dist;
                                            _suggestions = []; // Close suggestions
                                          });
                                        },
                                      );
                                    },
                                  ),
                                ),
                              ],
                              const SizedBox(height: 16),
                              Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: AppTheme.backgroundColor,
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Row(
                                  children: [
                                    const Icon(Icons.info_outline, size: 20, color: AppTheme.primaryColor),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            _distanceKm > 0 
                                                ? 'Distance: ${_distanceKm.toStringAsFixed(2)} KM'
                                                : 'Distance: Not calculated',
                                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppTheme.darkColor),
                                          ),
                                          const SizedBox(height: 2),
                                          Text(
                                            _distanceKm > 0
                                                ? 'Shipping Fee: Rp ${currencyFormat.format(_deliveryFeeTotal)}'
                                                : 'Select address from suggestions to calculate delivery fee.',
                                            style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 20),
                    ],

                    // Accessories Selection Card
                    const Text('Additional Accessories', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppTheme.darkColor)),
                    const SizedBox(height: 8),
                    _isLoadingAccessories
                        ? const Center(child: Padding(padding: EdgeInsets.all(8.0), child: CircularProgressIndicator()))
                        : _allAccessories.isEmpty
                            ? Card(
                                elevation: 0,
                                color: Colors.white,
                                child: Padding(
                                  padding: const EdgeInsets.all(12.0),
                                  child: Text('No accessories available.', style: TextStyle(color: Colors.grey.shade500)),
                                ),
                              )
                            : Column(
                                children: _allAccessories.map((acc) {
                                  final isSelected = _selectedAccessoryIds.contains(acc.id);
                                  return Card(
                                    elevation: 0,
                                    color: Colors.white,
                                    margin: const EdgeInsets.only(bottom: 8),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(12),
                                      side: BorderSide(color: isSelected ? AppTheme.primaryColor : Colors.grey.shade200),
                                    ),
                                    child: CheckboxListTile(
                                      activeColor: AppTheme.primaryColor,
                                      checkColor: AppTheme.darkColor,
                                      title: Text(acc.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                                      subtitle: Text(
                                        '+ Rp ${currencyFormat.format(acc.dailyPrice)}/day\n${acc.description ?? ""}',
                                        style: TextStyle(color: Colors.grey.shade600, fontSize: 11),
                                      ),
                                      value: isSelected,
                                      onChanged: (bool? checked) {
                                        setState(() {
                                          if (checked == true) {
                                            _selectedAccessoryIds.add(acc.id);
                                          } else {
                                            _selectedAccessoryIds.remove(acc.id);
                                          }
                                        });
                                      },
                                    ),
                                  );
                                }).toList(),
                              ),
                    const SizedBox(height: 20),

                    // Pricing Invoice Breakdown Card
                    const Text('Invoice Detail', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppTheme.darkColor)),
                    const SizedBox(height: 8),
                    Card(
                      elevation: 0,
                      color: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                        side: BorderSide(color: Colors.grey.shade200),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Column(
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text('Motor Rental (${widget.motorcycle.brand} x $_rentalDays days)', style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
                                Text('Rp ${currencyFormat.format(_basePriceTotal)}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                              ],
                            ),
                            if (_selectedAccessoryIds.isNotEmpty) ...[
                              ..._allAccessories.where((acc) => _selectedAccessoryIds.contains(acc.id)).map((acc) {
                                return Padding(
                                  padding: const EdgeInsets.only(top: 8.0),
                                  child: Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Text(
                                        'Accessory: ${acc.name} (x $_rentalDays days)',
                                        style: TextStyle(color: Colors.grey.shade700, fontSize: 13),
                                      ),
                                      Text(
                                        'Rp ${currencyFormat.format(acc.dailyPrice * _rentalDays)}',
                                        style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                                      ),
                                    ],
                                  ),
                                );
                              }).toList(),
                            ],
                            if (_deliveryType == 'delivery') ...[
                              const SizedBox(height: 8),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text('Delivery Shipping Fee (${_distanceKm.toStringAsFixed(0)} KM)', style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
                                  Text('Rp ${currencyFormat.format(_deliveryFeeTotal)}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                                ],
                              ),
                            ],
                            const Divider(height: 24),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                const Text('Total Payment', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                                Text(
                                  'Rp ${currencyFormat.format(_grandTotal)}',
                                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppTheme.primaryColor),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),

                    Row(
                      children: [
                        Container(
                          height: 54,
                          width: 54,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: AppTheme.primaryColor, width: 2),
                          ),
                          child: InkWell(
                            onTap: () => _submitBooking(payNow: false),
                            borderRadius: BorderRadius.circular(12),
                            child: const Stack(
                              alignment: Alignment.center,
                              children: [
                                Icon(
                                  Icons.shopping_cart_outlined,
                                  color: AppTheme.darkColor,
                                  size: 26,
                                ),
                                Positioned(
                                  right: 8,
                                  top: 8,
                                  child: Icon(
                                    Icons.add,
                                    color: AppTheme.darkColor,
                                    size: 14,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: SizedBox(
                            height: 54,
                            child: ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppTheme.primaryColor,
                                foregroundColor: AppTheme.darkColor,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                elevation: 0,
                              ),
                              onPressed: () => _submitBooking(payNow: true),
                              child: const Text(
                                'Confirm & Pay Now',
                                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}
