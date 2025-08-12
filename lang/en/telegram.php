<?php

return [
    'welcome' => [
        'title' => '🎉 Welcome to our service!',
        'description' => "I can help you with:\n• Get latest information\n• Receive important notifications\n• Contact customer support",
        'help_command' => 'Type /help to see more commands',
        'language_prompt' => 'Please choose your preferred language:'
    ],

    'language' => [
        'selection_title' => '🌍 Language Selection',
        'selection_prompt' => 'Please choose your preferred language:',
        'multilingual_title' => 'Please select your language',
        'multilingual_prompt' => 'After selecting a language, you will see the corresponding menu options.',
        'changed' => '✅ Language changed to English',
        'change_anytime' => 'You can change language anytime with /language command'
    ],

    'help' => [
        'title' => '📋 Available Commands:',
        'commands' => [
            '/start' => 'Start using the bot',
            '/help' => 'Show help',
            '/language' => 'Change language',
            '/contact' => 'Contact us',
            '/status' => 'Check status'
        ],
        'footer' => 'Send me any message and I will try to help!'
    ],

    'messages' => [
        'received' => 'Received your message: 「:message」',
        'support_reply' => 'Our support team will reply to you soon.',
        'urgent_help' => 'For urgent help, type /contact',
        'unknown_command' => 'Unknown command. Type /help to see available commands.',
        'processing' => 'Processing your request...',
        'broadcast' => 'Dear :name, we have an important announcement for you. Today is :date.',
        'welcome_new_user' => 'Welcome :name! Thank you for joining us on :date. Your preferred language is :language.',
        'system_maintenance' => 'Hello :name, we will perform system maintenance today (:date). Service may be temporarily unavailable.',
        'promotion' => 'Hi :name! We have a special offer for you. Check it out today (:date)!'
    ],

    'contact' => [
        'title' => '📞 Contact Information',
        'email' => 'Email: support@example.com',
        'phone' => 'Phone: +1-234-567-8900',
        'hours' => 'Business Hours: Mon-Fri 9AM-6PM',
        'response_time' => 'We typically respond within 24 hours'
    ],

    'status' => [
        'title' => '📊 Your Status',
        'user_id' => 'User ID: :id',
        'language' => 'Language: :language',
        'joined' => 'Joined: :date',
        'last_active' => 'Last Active: :date'
    ],

    'buttons' => [
        'english' => '🇺🇸 English',
        'chinese' => '🇨🇳 中文',
        'malay' => '🇲🇾 Bahasa Malaysia',
        'back' => '⬅️ Back',
        'confirm' => '✅ Confirm',
        'cancel' => '❌ Cancel'
    ]
];
