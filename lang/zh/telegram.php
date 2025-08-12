<?php

return [
    'welcome' => [
        'title' => '🎉 欢迎使用我们的服务！',
        'description' => "我可以帮助您：\n• 获取最新信息\n• 接收重要通知\n• 联系客服支持",
        'help_command' => '输入 /help 查看更多命令',
        'language_prompt' => '请选择您的首选语言：'
    ],
    
    'language' => [
        'selection_title' => '🌍 语言选择',
        'selection_prompt' => '请选择您的首选语言：',
        'multilingual_title' => 'Please select your language / 请选择您的语言 / Sila pilih bahasa anda',
        'multilingual_prompt' => 'After selecting a language, you will see the corresponding menu options. / 选择语言后，您将看到相应的菜单选项。 / Selepas memilih bahasa, anda akan melihat pilihan menu yang sepadan.',
        'changed' => '✅ 语言已切换为中文',
        'change_anytime' => '您可以随时使用 /language 命令更改语言'
    ],
    
    'help' => [
        'title' => '📋 可用命令：',
        'commands' => [
            '/start' => '开始使用',
            '/help' => '显示帮助',
            '/language' => '更改语言',
            '/contact' => '联系我们',
            '/status' => '查看状态'
        ],
        'footer' => '直接发送消息给我，我会尽力回复！'
    ],
    
    'messages' => [
        'received' => '收到您的消息：「:message」',
        'support_reply' => '我们的客服团队会尽快回复您。',
        'urgent_help' => '如需紧急帮助，请输入 /contact',
        'unknown_command' => '未知命令。输入 /help 查看可用命令。',
        'processing' => '正在处理您的请求...',
        'broadcast' => '亲爱的 :name，我们有重要通知给您。今天是 :date。',
        'welcome_new_user' => '欢迎 :name！感谢您在 :date 加入我们。您的首选语言是 :language。',
        'system_maintenance' => '您好 :name，我们将在今天（:date）进行系统维护。服务可能会暂时不可用。',
        'promotion' => '您好 :name！我们为您准备了特别优惠。今天（:date）来看看吧！'
    ],
    
    'contact' => [
        'title' => '📞 联系信息',
        'email' => '邮箱：support@example.com',
        'phone' => '电话：+1-234-567-8900',
        'hours' => '营业时间：周一至周五 上午9点-下午6点',
        'response_time' => '我们通常在24小时内回复'
    ],
    
    'status' => [
        'title' => '📊 您的状态',
        'user_id' => '用户ID：:id',
        'language' => '语言：:language',
        'joined' => '加入时间：:date',
        'last_active' => '最后活跃：:date'
    ],
    
    'buttons' => [
        'english' => '🇺🇸 English',
        'chinese' => '🇨🇳 中文',
        'malay' => '🇲🇾 Bahasa Malaysia',
        'back' => '⬅️ 返回',
        'confirm' => '✅ 确认',
        'cancel' => '❌ 取消'
    ]
];