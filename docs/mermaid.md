https://mermaid.live

```mermaid
graph TD;
    A["Telegram 用户"] -->|发送 /start 文本| B["Telegram 平台"];
    B -->|POST webhook| C["Laravel /telegram/webhook"];
    C --> D["Telegram::commandsHandler(true)"];
    D --> E["TelegramWebhookService.handleUpdate"];
    E -->|Message| F["TelegramMessageService.handleUserMessage"];
    E -->|CallbackQuery| R["CallbackRouter.route"];

    %% 文本 /start 的定向流程
    F -->|未选语言| G["sendLanguageSelection(语言选择键盘)"];
    F -->|已选语言 + /start| H{"查找 key=welcome_message"};
    H -- 找到 --> I["StartCommand.execute(user, msg, menuItemId=欢迎菜单ID)"];
    I --> J["渲染欢迎菜单的子菜单 (inline_keyboard)\n有图: 发送图片+caption(标题+描述)；无图: 发送纯文本"];
    H -- 未找到 --> K["回退：StartCommand.execute(user, msg) 显示根级菜单"];

    %% 语言选择成功后的首次展示
    G --> L["用户点击 lang_xx 回调"];
    L --> R;
    R --> M{"语言选择成功后查找 welcome_message"};
    M -- 找到 --> N["StartCommand.execute(user, msg, 欢迎菜单ID)"];
    M -- 未找到 --> O["回退：StartCommand.execute(user, msg)"];

    %% 子菜单浏览与返回
    J --> P["用户点击子项 -> CallbackRouter 继续分发 (menu_/callback/url)\n返回按钮文本来自 telegram_languages.back_label\n返回回调: back_to_{parentId} / back_to_root"];
    P --> Q["根据类型执行或进入下一级，并记录统计\n回调后发送一条消息：有图则图片+caption，无图则纯文本"];
```

```mermaid
graph TD;
    A["用户访问管理后台"] --> B["登录页面"];
    B --> C["选择语言 (英语/中文/马来语)"];
    C --> D["输入凭据登录"];
    D --> E["主应用界面"];
    E --> F["导航菜单 (多语言)"];
    F --> G["仪表板页面"];
    F --> H["用户管理页面"];
    F --> I["消息广播页面"];
    F --> J["菜单管理页面"];
    F --> K["图片管理页面"];
    F --> L["语言管理页面"];
    F --> M["个人资料页面"];
    G --> N["查看统计数据"];
    H --> O["管理 Telegram 用户"];
    I --> P["发送群发消息"];
    J --> Q["管理 Bot 菜单"];
    K --> R["管理 Bot 图片"];
    L --> S["管理多语言"];
    M --> T["更新账户信息"];
    N --> U("结束");
    O --> U;
    P --> U;
    Q --> U;
    R --> U;
    S --> U;
    T --> U;
```
```


