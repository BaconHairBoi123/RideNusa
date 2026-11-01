import '../api_config.dart';

class Motorcycle {
  final int id;
  final String category;
  final String brand;
  final String type;
  final String cc;
  final String transmission;
  final int price;
  final String? imageUrl;
  final String status;
  final String fuelConfiguration;
  final List<String> gallery;
  final String? description;

  Motorcycle({
    required this.id,
    required this.category,
    required this.brand,
    required this.type,
    required this.cc,
    required this.transmission,
    required this.price,
    this.imageUrl,
    required this.status,
    required this.fuelConfiguration,
    required this.gallery,
    this.description,
  });

  // Mengubah path gambar menjadi URL yang bisa diakses.
  static String? _resolveImageUrl(dynamic value) {
    if (value == null) return null;

    final String path = value.toString().trim();

    if (path.isEmpty || path.toLowerCase() == 'null') {
      return null;
    }

    // Jika sudah berupa URL lengkap (Supabase atau URL lainnya).
    if (path.startsWith('https://') || path.startsWith('http://')) {
      return path;
    }

    // Jika berupa path relatif.
    return '${ApiConfig.imageUrl}/${path.replaceFirst(RegExp(r'^/+'), '')}';
  }

  factory Motorcycle.fromJson(Map<String, dynamic> json) {
    String typeRaw = json['type'] ?? '';
    String typeFormatted = typeRaw.replaceAll('_', ' ');

    typeFormatted = typeFormatted
        .split(' ')
        .map((str) => str.isNotEmpty
            ? '${str[0].toUpperCase()}${str.substring(1)}'
            : '')
        .join(' ');

    int priceInt = 0;

    if (json['price'] != null) {
      if (json['price'] is String) {
        priceInt = double.tryParse(json['price'])?.toInt() ?? 0;
      } else if (json['price'] is num) {
        priceInt = (json['price'] as num).toInt();
      }
    }

    // Prioritaskan image_url karena bisa berupa URL publik Supabase.
    // image_path hanya digunakan sebagai fallback.
    final String? imgUrl =
        _resolveImageUrl(json['image_url']) ??
        _resolveImageUrl(json['image_path']);

    final List<dynamic> galleryJson =
        json['images'] ?? json['gallery'] ?? [];

    final List<String> galleryList = [];

    for (var img in galleryJson) {
      if (img is Map) {
        final String? galleryUrl =
            _resolveImageUrl(img['image_url']) ??
            _resolveImageUrl(img['image_path']);

        if (galleryUrl != null) {
          galleryList.add(galleryUrl);
        }
      }
    }

    if (imgUrl != null && !galleryList.contains(imgUrl)) {
      galleryList.insert(0, imgUrl);
    }

    return Motorcycle(
      id: json['id'],
      category: json['category'] ?? '',
      brand: json['brand'] ?? '',
      type: typeFormatted,
      cc: json['cc']?.toString() ?? '',
      transmission: json['transmission'] ?? 'Matic',
      price: priceInt,
      imageUrl: imgUrl,
      status: json['status'] ?? 'Available',
      fuelConfiguration: json['fuel_configuration'] ?? '',
      gallery: galleryList,
      description: json['description'],
    );
  }
}