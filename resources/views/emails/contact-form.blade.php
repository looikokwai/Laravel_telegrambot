<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contact Form Submission</title>
</head>
<body>
    <h2>新联系表单提交</h2>
    <p><strong>姓名：</strong> {{ $data['name'] }}</p>
    <p><strong>邮箱：</strong> {{ $data['email'] }}</p>
    <p><strong>电话：</strong> {{ $data['phone'] ?? '未填写' }}</p>
    <p><strong>公司：</strong> {{ $data['company'] ?? '未填写' }}</p>
    <p><strong>服务需求：</strong> {{ $data['service'] }}</p>
    <p><strong>留言：</strong> {{ $data['message'] }}</p>
</body>
</html>
