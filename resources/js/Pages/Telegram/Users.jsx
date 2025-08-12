import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button, Card, Input } from '@/Components/UI';
import { FaSearch, FaUserCheck, FaUserTimes } from 'react-icons/fa';

export default function TelegramUsers({ users }) {
    const [search, setSearch] = useState('');
    
    const { post } = useForm();

    const handleToggleStatus = (userId) => {
        post(`/telegram/users/${userId}/toggle-status`);
    };

    // 安全地处理用户数据
    const allUsers = users?.data || [];
    const filteredUsers = allUsers.filter(user => {
        if (!search) return true;
        const searchLower = search.toLowerCase();
        return (
            user.first_name?.toLowerCase().includes(searchLower) ||
            user.username?.toLowerCase().includes(searchLower) ||
            user.telegram_user_id?.includes(search)
        );
    });

    return (
        <>
            <Head title="用户管理" />
            
            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">用户管理</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            管理所有Telegram Bot用户 ({allUsers.length} 个用户)
                        </p>
                    </div>

                    <Card>
                        <div className="mb-6 flex items-center space-x-4">
                            <div className="relative max-w-md">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <FaSearch className="h-4 w-4 text-gray-400" />
                                </div>
                                <Input
                                    placeholder="搜索用户名、姓名或ID..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            用户
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            语言
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            状态
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            最后活跃
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            操作
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {filteredUsers.map((user) => (
                                        <tr key={user.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <div className="flex-shrink-0 h-10 w-10">
                                                        <div className="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                            <span className="text-sm font-medium text-gray-700">
                                                                {(user.first_name || 'U')[0].toUpperCase()}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="ml-4">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {user.first_name || 'Unknown'}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            @{user.username || 'no_username'}
                                                        </div>
                                                        <div className="text-xs text-gray-400">
                                                            ID: {user.telegram_user_id}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="inline-flex px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-md">
                                                    {user.language || 'en'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                                    user.is_active 
                                                        ? 'bg-green-100 text-green-800' 
                                                        : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {user.is_active ? '活跃' : '非活跃'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {user.last_interaction ? new Date(user.last_interaction).toLocaleString('zh-CN') : '从未'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <Button
                                                    onClick={() => handleToggleStatus(user.id)}
                                                    variant={user.is_active ? "danger" : "success"}
                                                    size="sm"
                                                    className="inline-flex items-center space-x-1"
                                                >
                                                    {user.is_active ? (
                                                        <>
                                                            <FaUserTimes className="w-3 h-3" />
                                                            <span>禁用</span>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <FaUserCheck className="w-3 h-3" />
                                                            <span>启用</span>
                                                        </>
                                                    )}
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {filteredUsers.length === 0 && (
                            <div className="text-center py-12">
                                <div className="text-gray-500">
                                    {search ? '没有找到匹配的用户' : '暂无用户数据'}
                                </div>
                            </div>
                        )}

                        {/* 分页信息 */}
                        {users?.meta && (
                            <div className="mt-6 flex items-center justify-between border-t border-gray-200 pt-6">
                                <div className="text-sm text-gray-700">
                                    显示 {users.meta.from || 0} 到 {users.meta.to || 0} 条，共 {users.meta.total || 0} 条记录
                                </div>
                                <div className="text-sm text-gray-500">
                                    第 {users.meta.current_page || 1} 页，共 {users.meta.last_page || 1} 页
                                </div>
                            </div>
                        )}
                    </Card>
                </div>
            </div>
        </>
    );
}

    // 处理删除用户
    const handleDelete = (userId) => {
        if (confirm('确定要删除这个用户吗？')) {
            router.delete(`/telegram/users/${userId}`, {
                onSuccess: () => {
                    router.reload({ only: ['users', 'stats'] });
                },
                onError: (errors) => {
                    toast.error(errors.error || '删除失败');
                }
            });
        }
    };

    // 处理批量删除
    const handleBulkDelete = () => {
        if (confirm(`确定要删除选中的 ${selectedUsers.size} 个用户吗？`)) {
            router.post('/telegram/users/bulk-delete', {
                ids: Array.from(selectedUsers)
            }, {
                onSuccess: () => {
                    setSelectedUsers(new Set());
                    router.reload({ only: ['users', 'stats'] });
                },
                onError: (errors) => {
                    toast.error(errors.error || '批量删除失败');
                }
            });
        }
    };

    // 处理切换用户封禁状态
    const handleToggleBlock = (userId, isBlocked) => {
        const action = isBlocked ? 'unblock' : 'block';
        if (confirm(`确定要${isBlocked ? '解封' : '封禁'}这个用户吗？`)) {
            router.post(`/telegram/users/${userId}/${action}`, {}, {
                onSuccess: () => {
                    router.reload({ only: ['users', 'stats'] });
                },
                onError: (errors) => {
                    toast.error(errors.error || '操作失败');
                }
            });
        }
    };

    // 处理搜索
    const handleSearch = (search) => {
        setSearch(search);
    };