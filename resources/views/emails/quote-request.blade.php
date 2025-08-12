<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quote Request Submission</title>
</head>
<body>
    <h2>新报价请求提交</h2>
    <p><strong>姓名：</strong> {{ $data['name'] }}</p>
    <p><strong>邮箱：</strong> {{ $data['email'] }}</p>
    <p><strong>电话：</strong> {{ $data['phone'] ?? '未填写' }}</p>
    <p><strong>公司：</strong> {{ $data['company'] }}</p>
    <p><strong>网站：</strong> {{ $data['website'] ?? '未填写' }}</p>
    <p><strong>服务需求：</strong> {{ implode(', ', $data['services'] ?? []) }}</p>
    <p><strong>预算：</strong> {{ $data['budget'] }}</p>
    <p><strong>时间要求：</strong> {{ $data['timeline'] }}</p>
    <p><strong>留言：</strong> {{ $data['message'] ?? '未填写' }}</p>
</body>
</html>
