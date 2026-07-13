import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../../core/app_theme.dart';
import '../../../../REST-API/api_config.dart';

class ChatMessage {
  final String text;
  final bool isUser;
  final DateTime timestamp;

  ChatMessage({
    required this.text,
    required this.isUser,
    required this.timestamp,
  });
}

class ChatbotScreen extends StatefulWidget {
  const ChatbotScreen({super.key});

  @override
  State<ChatbotScreen> createState() => _ChatbotScreenState();
}

class _ChatbotScreenState extends State<ChatbotScreen> {
  final List<ChatMessage> _messages = [];
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  bool _isTyping = false;
  String _userName = 'Guest';
  String _userId = 'guest_id';
  String _sessionId = '';
  bool _isOnline = true;
  bool _isCheckingStatus = true;

  @override
  void initState() {
    super.initState();
    _loadUserInfo();
    _checkChatbotStatus();
    // Add welcome message
    _messages.add(ChatMessage(
      text: "Halo! Saya adalah RideNusa AI Assistant. Ada yang bisa saya bantu hari ini mengenai penyewaan motor?",
      isUser: false,
      timestamp: DateTime.now(),
    ));
  }

  Future<void> _checkChatbotStatus() async {
    setState(() {
      _isCheckingStatus = true;
    });
    try {
      final response = await http.get(
        Uri.parse(ApiConfig.chatbotWebhookUrl),
      ).timeout(const Duration(seconds: 4));
      
      setState(() {
        _isOnline = response.statusCode != 502 && response.statusCode != 503 && response.statusCode != 504;
        _isCheckingStatus = false;
      });
    } catch (_) {
      setState(() {
        _isOnline = false;
        _isCheckingStatus = false;
      });
    }
  }

  Future<void> _loadUserInfo() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _userName = prefs.getString('user_name') ?? 'Guest';
      _userId = prefs.getString('user_id') ?? 'guest';
      _sessionId = 'session_${_userId}_${DateTime.now().millisecondsSinceEpoch}';
    });
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _sendMessage() async {
    final text = _messageController.text.trim();
    if (text.isEmpty) return;

    _messageController.clear();
    setState(() {
      _messages.add(ChatMessage(
        text: text,
        isUser: true,
        timestamp: DateTime.now(),
      ));
      _isTyping = true;
    });
    _scrollToBottom();

    try {
      final response = await http.post(
        Uri.parse(ApiConfig.chatbotWebhookUrl),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'message': text,
          'user_id': _userId,
          'user_name': _userName,
          'session_id': _sessionId,
          'platform': 'mobile',
        }),
      );

      if (response.statusCode == 200) {
        final dynamic data = json.decode(response.body);
        final dynamic result = data is List ? data[0] : data;
        String botReply = result['reply'] ?? result['output'] ?? result['message'] ?? 'Koneksi sukses, namun format respons bot tidak cocok.';
        
        // Remove '=' prefix if exists (expression bug in n8n)
        botReply = botReply.replaceFirst(RegExp(r'^='), '');

        // Remove 'Tolong ya!' or 'Tolong ya, ' prefix
        botReply = botReply.replaceFirst(RegExp(r'^Tolong ya[!,]?\s*', caseSensitive: false), '');
        if (botReply.isNotEmpty) {
          botReply = botReply[0].toUpperCase() + botReply.substring(1);
        }

        setState(() {
          _messages.add(ChatMessage(
            text: botReply,
            isUser: false,
            timestamp: DateTime.now(),
          ));
        });
      } else {
        setState(() {
          _messages.add(ChatMessage(
            text: 'Mohon maaf, server sedang sibuk. Silakan coba sesaat lagi.',
            isUser: false,
            timestamp: DateTime.now(),
          ));
        });
      }
    } catch (e) {
      setState(() {
        _messages.add(ChatMessage(
          text: 'Mohon maaf, koneksi ke chatbot terputus. Pastikan server atau tunnel n8n aktif.',
          isUser: false,
          timestamp: DateTime.now(),
        ));
      });
    } finally {
      setState(() {
        _isTyping = false;
      });
      _scrollToBottom();
    }
  }

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        backgroundColor: AppTheme.primaryColor,
        elevation: 1,
        automaticallyImplyLeading: true,
        iconTheme: const IconThemeData(color: AppTheme.darkColor),
        title: Row(
          children: [
            CircleAvatar(
              backgroundColor: AppTheme.darkColor.withOpacity(0.1),
              child: const Icon(Icons.smart_toy_outlined, color: AppTheme.darkColor),
            ),
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'RideNusa Assistant',
                  style: TextStyle(
                    color: AppTheme.darkColor,
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text(
                  _isCheckingStatus
                      ? 'Checking...'
                      : (_isOnline ? 'Online' : 'Offline'),
                  style: TextStyle(
                    color: _isCheckingStatus
                        ? Colors.orange.shade800
                        : (_isOnline ? Colors.green : Colors.redAccent),
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
      body: Column(
        children: [
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
              itemCount: _messages.length,
              itemBuilder: (context, index) {
                final message = _messages[index];
                return _buildMessageItem(message);
              },
            ),
          ),
          if (_isTyping)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
              child: Row(
                children: [
                  const SizedBox(
                    width: 14,
                    height: 14,
                    child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.primaryColor),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    'RideNusa Bot is typing...',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600, fontStyle: FontStyle.italic),
                  ),
                ],
              ),
            ),
          // WhatsApp button located above the send message button
          Align(
            alignment: Alignment.centerRight,
            child: Padding(
              padding: const EdgeInsets.only(right: 16.0, bottom: 8.0),
              child: InkWell(
                onTap: () async {
                  final Uri url = Uri.parse('https://wa.me/6281234567890');
                  if (await canLaunchUrl(url)) {
                    await launchUrl(url, mode: LaunchMode.externalApplication);
                  }
                },
                borderRadius: BorderRadius.circular(22),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade300),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withOpacity(0.05),
                            blurRadius: 4,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: const Text(
                        'Chat Admin',
                        style: TextStyle(
                          color: AppTheme.darkColor,
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Image.asset(
                      'assets/images/whatsapp.png',
                      width: 40,
                      height: 40,
                    ),
                  ],
                ),
              ),
            ),
          ),
          _buildInputArea(),
        ],
      ),
    );
  }

  Widget _buildMessageItem(ChatMessage message) {
    return Align(
      alignment: message.isUser ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.75,
        ),
        decoration: BoxDecoration(
          color: message.isUser ? AppTheme.primaryColor : Colors.white,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: message.isUser ? const Radius.circular(16) : Radius.zero,
            bottomRight: message.isUser ? Radius.zero : const Radius.circular(16),
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.03),
              blurRadius: 4,
              offset: const Offset(0, 2),
            )
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              message.text,
              style: TextStyle(
                color: message.isUser ? AppTheme.darkColor : Colors.black87,
                fontSize: 14,
                height: 1.3,
              ),
            ),
            const SizedBox(height: 4),
            Align(
              alignment: Alignment.bottomRight,
              child: Text(
                '${message.timestamp.hour.toString().padLeft(2, '0')}:${message.timestamp.minute.toString().padLeft(2, '0')}',
                style: TextStyle(
                  color: message.isUser ? AppTheme.darkColor.withOpacity(0.6) : Colors.grey.shade500,
                  fontSize: 10,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInputArea() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, -4),
          )
        ],
      ),
      child: SafeArea(
        child: Row(
          children: [
            Expanded(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                decoration: BoxDecoration(
                  color: Colors.grey.shade100,
                  borderRadius: BorderRadius.circular(24),
                ),
                child: TextField(
                  controller: _messageController,
                  textInputAction: TextInputAction.send,
                  onSubmitted: (_) => _isOnline ? _sendMessage() : null,
                  enabled: _isOnline,
                  decoration: InputDecoration(
                    hintText: _isOnline ? 'Type a message...' : 'Chatbot offline',
                    border: InputBorder.none,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 8),
            InkWell(
              onTap: _isOnline ? _sendMessage : null,
              borderRadius: BorderRadius.circular(24),
              child: CircleAvatar(
                radius: 22,
                backgroundColor: _isOnline ? AppTheme.primaryColor : Colors.grey.shade300,
                child: Icon(
                  Icons.send,
                  color: _isOnline ? AppTheme.darkColor : Colors.grey.shade500,
                  size: 20,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
