import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/app_theme.dart';
import '../../../REST-API/api_config.dart';
import '../../../REST-API/Services/booking_service.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  final BookingService _bookingService = BookingService();
  
  List<Map<String, dynamic>> _allBookings = [];
  bool _isLoading = true;
  final currencyFormat = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  @override
  void initState() {
    super.initState();
    _loadBookings();
  }

  Future<void> _loadBookings() async {
    setState(() => _isLoading = true);
    try {
      final list = await _bookingService.getBookingHistory();
      setState(() {
        _allBookings = list;
        _isLoading = false;
      });
    } catch (_) {
      setState(() => _isLoading = false);
    }
  }

  bool _isActive(Map<String, dynamic> booking) {
    final status = booking['payment_status']?.toString().toLowerCase();
    if (status != 'paid' && status != 'settlement') return false;
    
    final bool hasReturn = booking['has_return'] == true;
    return !hasReturn;
  }

  List<Map<String, dynamic>> get _historyBookings {
    return _allBookings.where((b) => !_isActive(b)).toList();
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

  Widget _buildStatusBadge(Map<String, dynamic> booking) {
    final status = (booking['payment_status']?.toString() ?? 'pending').toLowerCase();
    Color bgColor;
    Color textColor;
    String label;

    if (status == 'paid' || status == 'settlement') {
      bgColor = Colors.blue.shade50;
      textColor = Colors.blue.shade700;
      label = 'Completed';
    } else if (status == 'pending') {
      bgColor = Colors.amber.shade50;
      textColor = Colors.amber.shade800;
      label = 'Pending Payment';
    } else if (status == 'cancelled' || status == 'cancel') {
      bgColor = Colors.red.shade50;
      textColor = Colors.red;
      label = 'Cancelled';
    } else {
      bgColor = Colors.red.shade50;
      textColor = Colors.red;
      label = 'Failed';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: textColor,
          fontSize: 11,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }

  Widget _buildHistoryCard(Map<String, dynamic> booking) {
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

    final paymentStatus = (booking['payment_status']?.toString() ?? 'pending').toLowerCase();
    final snapToken = booking['snap_token'];

    return Card(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Colors.grey.shade200),
      ),
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: Container(
                    width: 70,
                    height: 70,
                    color: AppTheme.backgroundColor,
                    child: imageUrl.isNotEmpty
                        ? CachedNetworkImage(
                            imageUrl: imageUrl,
                            fit: BoxFit.cover,
                            placeholder: (context, url) => const Center(
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.amber,
                              ),
                            ),
                            errorWidget: (context, url, error) => const Center(
                              child: Icon(Icons.two_wheeler, color: Colors.grey),
                            ),
                          )
                        : const Center(child: Icon(Icons.two_wheeler, color: Colors.grey)),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        brand,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppTheme.darkColor),
                      ),
                      if (category.isNotEmpty) ...[
                        const SizedBox(height: 2),
                        Text(
                          category,
                          style: TextStyle(color: Colors.grey.shade500, fontSize: 12),
                        ),
                      ],
                      const SizedBox(height: 4),
                      Text(
                        'Plate Number: ${mc['license_plate'] ?? '-'}',
                        style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12, color: AppTheme.darkColor),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          const Icon(Icons.calendar_today, size: 12, color: AppTheme.primaryColor),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              '$startDateStr to $endDateStr',
                              style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            const Divider(height: 1),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Total Payment', style: TextStyle(color: Colors.grey.shade500, fontSize: 11)),
                    const SizedBox(height: 2),
                    Text(
                      currencyFormat.format(totalVal),
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.red),
                    ),
                  ],
                ),
                _buildStatusBadge(booking),
              ],
            ),
            if (paymentStatus == 'pending' && snapToken != null) ...[
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () async {
                    final String paymentUrl = 'https://app.sandbox.midtrans.com/snap/v2/vtweb/$snapToken';
                    final Uri url = Uri.parse(paymentUrl);
                    if (await canLaunchUrl(url)) {
                      await launchUrl(url, mode: LaunchMode.inAppWebView);
                    }
                  },
                  icon: const Icon(Icons.payment, size: 18),
                  label: const Text('Pay Now'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primaryColor,
                    foregroundColor: AppTheme.darkColor,
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final history = _historyBookings;

    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0.5,
        iconTheme: const IconThemeData(color: AppTheme.darkColor),
        title: const Text(
          'Rental History',
          style: TextStyle(color: AppTheme.darkColor, fontWeight: FontWeight.bold, fontSize: 18),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _loadBookings,
        color: AppTheme.primaryColor,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator(valueColor: AlwaysStoppedAnimation<Color>(AppTheme.primaryColor)))
            : history.isEmpty
                ? ListView(
                    children: [
                      SizedBox(height: MediaQuery.of(context).size.height * 0.25),
                      Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.history, size: 64, color: Colors.grey.shade400),
                            const SizedBox(height: 16),
                            const Text(
                              'No History Found',
                              style: TextStyle(color: AppTheme.darkColor, fontSize: 18, fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              'Your past rentals and transactions will appear here.',
                              style: TextStyle(color: Colors.grey.shade500, fontSize: 14),
                            ),
                          ],
                        ),
                      ),
                    ],
                  )
                : ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: history.length,
                    itemBuilder: (context, index) {
                      return _buildHistoryCard(history[index]);
                    },
                  ),
      ),
    );
  }
}
