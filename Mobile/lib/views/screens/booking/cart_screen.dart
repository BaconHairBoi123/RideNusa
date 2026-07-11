import 'dart:async';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/app_theme.dart';
import '../../../core/dialog_helper.dart';
import '../../../REST-API/api_config.dart';
import '../../../REST-API/Services/booking_service.dart';

class CartScreen extends StatefulWidget {
  const CartScreen({super.key});

  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  final BookingService _bookingService = BookingService();
  
  List<Map<String, dynamic>> _allBookings = [];
  bool _isLoading = true;
  final currencyFormat = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);
  Timer? _pollingTimer;

  @override
  void initState() {
    super.initState();
    _loadBookings();
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    super.dispose();
  }

  Future<void> _loadBookings() async {
    setState(() => _isLoading = true);
    try {
      final list = await _bookingService.getBookingHistory();
      setState(() {
        _allBookings = list;
        _isLoading = false;
      });
      _checkAndStartPolling();
    } catch (_) {
      setState(() => _isLoading = false);
    }
  }

  void _checkAndStartPolling() {
    _pollingTimer?.cancel();
    
    // Find if there is any pending booking
    final pendingBooking = _allBookings.firstWhere(
      (b) => b['payment_status']?.toString().toLowerCase() == 'pending',
      orElse: () => <String, dynamic>{},
    );
    
    if (pendingBooking.isNotEmpty) {
      final orderId = pendingBooking['order_id'];
      if (orderId != null) {
        _pollingTimer = Timer.periodic(const Duration(seconds: 3), (timer) async {
          try {
            final list = await _bookingService.getBookingHistory();
            final updated = list.firstWhere(
              (b) => b['order_id'] == orderId,
              orElse: () => <String, dynamic>{},
            );
            if (updated.isNotEmpty) {
              final status = updated['payment_status']?.toString().toLowerCase() ?? '';
              if (status == 'paid' || status == 'settlement' || status == 'success') {
                timer.cancel();
                await closeInAppWebView();
                try {
                  final newList = await _bookingService.getBookingHistory();
                  setState(() {
                    _allBookings = newList;
                  });
                } catch (_) {}
                if (mounted) {
                  DialogHelper.showMessage(
                    context: context,
                    message: 'Payment successful! Status is now active.',
                    isError: false,
                  );
                }
              }
            }
          } catch (e) {
            debugPrint('Polling error: $e');
          }
        });
      }
    }
  }

  bool _isActive(Map<String, dynamic> booking) {
    final status = booking['payment_status']?.toString().toLowerCase();
    if (status != 'paid' && status != 'settlement') return false;
    
    final bool hasReturn = booking['has_return'] == true;
    return !hasReturn;
  }

  Map<String, dynamic>? get _currentBooking {
    if (_allBookings.isEmpty) return null;
    
    // Find the first active rental if it exists
    for (var b in _allBookings) {
      if (_isActive(b)) {
        return b;
      }
    }
    
    // Otherwise, check if the latest booking is pending
    final latest = _allBookings.first;
    final status = latest['payment_status']?.toString().toLowerCase() ?? '';
    if (status == 'pending') {
      return latest;
    }
    
    return null;
  }

  String _resolveImageUrl(Map<String, dynamic> booking) {
    if (booking['motorcycle'] == null) return '';
    final mc = booking['motorcycle'];
    final String? apiImgUrl = mc['image_url'];
    final String? path = mc['image_path'];

    if (apiImgUrl != null && apiImgUrl.isNotEmpty) {
      if (apiImgUrl.contains('/storage/motorcycles/motorcycles/')) {
        return apiImgUrl.replaceAll('/storage/motorcycles/motorcycles/', '/storage/motorcycles/');
      }
      return apiImgUrl;
    }
    
    if (path != null && path.isNotEmpty) {
      return '${ApiConfig.imageUrl}/$path';
    }
    
    return '';
  }

  Widget _buildActiveCard(Map<String, dynamic> booking) {
    final mc = booking['motorcycle'] ?? {};
    final brand = mc['brand'] ?? 'Motorcycle';
    final category = mc['category'] ?? '';
    final String imageUrl = _resolveImageUrl(booking);

    final startDateStr = booking['start_date'] ?? '';
    final endDateStr = booking['end_date'] ?? '';
    
    double totalVal = 0.0;
    if (booking['total_price'] != null) {
      if (booking['total_price'] is num) {
        totalVal = (booking['total_price'] as num).toDouble();
      } else {
        totalVal = double.tryParse(booking['total_price'].toString()) ?? 0.0;
      }
    }

    final status = (booking['payment_status']?.toString() ?? 'pending').toLowerCase();
    final isPending = status == 'pending';
    final snapToken = booking['snap_token'];

    bool isOverdue = false;
    if (endDateStr.isNotEmpty) {
      try {
        final endDate = DateTime.parse(endDateStr);
        final now = DateTime.now();
        final today = DateTime(now.year, now.month, now.day);
        if (endDate.isBefore(today)) {
          isOverdue = true;
        }
      } catch (_) {}
    }

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Colors.grey.shade200),
      ),
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(12),
                  child: Container(
                    width: 90,
                    height: 90,
                    color: AppTheme.backgroundColor,
                    child: imageUrl.isNotEmpty
                        ? Image.network(
                            imageUrl,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) {
                              return const Center(child: Icon(Icons.two_wheeler, color: Colors.grey, size: 36));
                            },
                          )
                        : const Center(child: Icon(Icons.two_wheeler, color: Colors.grey, size: 36)),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              brand,
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: AppTheme.darkColor),
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: isPending 
                                  ? Colors.amber.shade50 
                                  : (isOverdue ? Colors.red.shade50 : Colors.green.shade50),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              isPending 
                                  ? 'Pending Payment' 
                                  : (isOverdue ? 'Overdue' : 'Active'),
                              style: TextStyle(
                                color: isPending 
                                    ? Colors.amber.shade800 
                                    : (isOverdue ? Colors.red.shade700 : Colors.green),
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                      if (category.isNotEmpty) ...[
                        const SizedBox(height: 2),
                        Text(
                          category,
                          style: TextStyle(color: Colors.grey.shade500, fontSize: 13),
                        ),
                      ],
                      const SizedBox(height: 4),
                      Text(
                        'Plate Number: ${mc['license_plate'] ?? '-'}',
                        style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: AppTheme.darkColor),
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          const Icon(Icons.calendar_today, size: 14, color: AppTheme.primaryColor),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              '$startDateStr to $endDateStr',
                              style: TextStyle(fontSize: 13, color: Colors.grey.shade700),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),
            const Divider(height: 1),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Total Rental Cost', style: TextStyle(color: Colors.grey.shade500, fontSize: 12)),
                    const SizedBox(height: 2),
                    Text(
                      currencyFormat.format(totalVal),
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.red),
                    ),
                  ],
                ),
                isPending
                    ? Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          ElevatedButton.icon(
                            onPressed: () async {
                              final confirm = await showDialog<bool>(
                                context: context,
                                builder: (ctx) => AlertDialog(
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                  title: const Text('Cancel Booking?'),
                                  content: const Text('Are you sure you want to cancel this booking?'),
                                  actions: [
                                    TextButton(
                                      onPressed: () => Navigator.pop(ctx, false),
                                      child: const Text('No', style: TextStyle(color: Colors.grey)),
                                    ),
                                    ElevatedButton(
                                      onPressed: () => Navigator.pop(ctx, true),
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: Colors.red,
                                        foregroundColor: Colors.white,
                                        elevation: 0,
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                      ),
                                      child: const Text('Yes, Cancel', style: TextStyle(fontWeight: FontWeight.bold)),
                                    ),
                                  ],
                                ),
                              );

                              if (confirm == true) {
                                setState(() => _isLoading = true);
                                final orderId = booking['order_id'];
                                final res = await _bookingService.cancelBooking(orderId);
                                if (res['success'] == true) {
                                  if (mounted) {
                                    DialogHelper.showMessage(
                                      context: context,
                                      message: res['message'] ?? 'Booking cancelled successfully.',
                                      isError: false,
                                    );
                                  }
                                  _loadBookings();
                                } else {
                                  setState(() => _isLoading = false);
                                  if (mounted) {
                                    DialogHelper.showMessage(
                                      context: context,
                                      message: res['message'] ?? 'Failed to cancel booking.',
                                      isError: true,
                                    );
                                  }
                                }
                              }
                            },
                            icon: const Icon(Icons.cancel_outlined, size: 14, color: Colors.white),
                            label: const Text('Cancel', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.red,
                              foregroundColor: Colors.white,
                              elevation: 0,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            ),
                          ),
                          const SizedBox(width: 8),
                          ElevatedButton.icon(
                            onPressed: () async {
                              if (snapToken != null) {
                                final String paymentUrl = 'https://app.sandbox.midtrans.com/snap/v2/vtweb/$snapToken';
                                final Uri url = Uri.parse(paymentUrl);
                                if (await canLaunchUrl(url)) {
                                  await launchUrl(url, mode: LaunchMode.inAppWebView);
                                }
                              }
                            },
                            icon: const Icon(Icons.payment, size: 14),
                            label: const Text('Pay Now', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppTheme.primaryColor,
                              foregroundColor: AppTheme.darkColor,
                              elevation: 0,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                            ),
                          ),
                        ],
                      )
                    : ElevatedButton.icon(
                        onPressed: () {
                          Navigator.pop(context, 2); 
                        },
                        icon: const Icon(Icons.my_location, size: 14),
                        label: const Text('Track GPS', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppTheme.primaryColor,
                          foregroundColor: AppTheme.darkColor,
                          elevation: 0,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                        ),
                      ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final booking = _currentBooking;

    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0.5,
        iconTheme: const IconThemeData(color: AppTheme.darkColor),
        title: const Text(
          'My Rental',
          style: TextStyle(color: AppTheme.darkColor, fontWeight: FontWeight.bold, fontSize: 18),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _loadBookings,
        color: AppTheme.primaryColor,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator(valueColor: AlwaysStoppedAnimation<Color>(AppTheme.primaryColor)))
            : booking == null
                ? ListView(
                    children: [
                      SizedBox(height: MediaQuery.of(context).size.height * 0.25),
                      Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Container(
                              padding: const EdgeInsets.all(20),
                              decoration: BoxDecoration(
                                color: Colors.grey.shade100,
                                shape: BoxShape.circle,
                              ),
                              child: Icon(Icons.motorcycle_outlined, size: 64, color: Colors.grey.shade400),
                            ),
                            const SizedBox(height: 20),
                            const Text(
                              'No Active Rental',
                              style: TextStyle(color: AppTheme.darkColor, fontSize: 18, fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 8),
                            Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 40.0),
                              child: Text(
                                "You don't have any ongoing motorcycle rentals or pending bookings at the moment.",
                                style: TextStyle(color: Colors.grey.shade500, fontSize: 14),
                                textAlign: TextAlign.center,
                              ),
                            ),
                            const SizedBox(height: 24),
                            ElevatedButton(
                              onPressed: () {
                                Navigator.pop(context, 0);
                              },
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppTheme.primaryColor,
                                foregroundColor: AppTheme.darkColor,
                                elevation: 0,
                                padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 14),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                              ),
                              child: const Text(
                                'Rent Now',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  )
                : ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      _buildActiveCard(booking),
                    ],
                  ),
      ),
    );
  }
}
