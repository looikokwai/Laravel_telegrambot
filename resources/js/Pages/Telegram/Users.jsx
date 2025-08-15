import React, { useState, useEffect, useCallback } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { Button, Card, Input } from '@/Components/UI';
import { useTranslation } from 'react-i18next';
import { FaSearch, FaUserCheck, FaUserTimes, FaSpinner } from 'react-icons/fa';

export default function TelegramUsers({ users }) {
    const { t } = useTranslation();
    const [search, setSearch] = useState('');
    const [searchLoading, setSearchLoading] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');

    const { post } = useForm();

    // 防抖搜索函数
    const debouncedSearch = useCallback(
        (() => {
            let timeoutId;
            return (query) => {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    setSearchLoading(true);
                    router.get('/telegram/users', { search: query }, {
                        preserveState: true,
                        onFinish: () => setSearchLoading(false)
                    });
                }, 300);
            };
        })(),
        []
    );

    // 监听搜索输入变化
    useEffect(() => {
        if (search !== searchQuery) {
            setSearchQuery(search);
            debouncedSearch(search);
        }
    }, [search, searchQuery, debouncedSearch]);

    const handleToggleStatus = (userId) => {
        post(`/telegram/users/${userId}/toggle-status`);
    };

    // 安全地处理用户数据
    const allUsers = users?.data || [];

    return (
        <>
            <Head title={t('telegram.users.userManagement')} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">{t('telegram.users.userManagement')}</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            {t('telegram.users.userManagementSubtitle')} ({allUsers.length} {t('telegram.users.foundUsers')})
                        </p>
                    </div>

                    <Card>
                        <div className="mb-6 flex items-center space-x-4">
                            <div className="relative max-w-md">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    {searchLoading ? (
                                        <FaSpinner className="h-4 w-4 text-blue-500 animate-spin" />
                                    ) : (
                                        <FaSearch className="h-4 w-4 text-gray-400" />
                                    )}
                                </div>
                                <Input
                                    placeholder={t('telegram.users.searchPlaceholder')}
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                    disabled={searchLoading}
                                />
                            </div>
                            {search && (
                                <div className="text-sm text-gray-500">
                                    {t('telegram.users.searchResults')}: "{search}" - {t('telegram.users.foundUsers')} {allUsers.length}
                                </div>
                            )}
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {t('telegram.users.user')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {t('telegram.users.language')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {t('telegram.users.status')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {t('telegram.users.lastActive')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {t('telegram.users.operations')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {allUsers.map((user) => (
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
                                                            {user.first_name || t('telegram.users.unknown')}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            @{user.username || t('telegram.users.noUsername')}
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
                                                    {user.is_active ? t('telegram.users.active') : t('telegram.users.inactive')}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {user.last_interaction ? new Date(user.last_interaction).toLocaleString() : t('telegram.users.never')}
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
                                                            <span>{t('telegram.users.disable')}</span>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <FaUserCheck className="w-3 h-3" />
                                                            <span>{t('telegram.users.enable')}</span>
                                                        </>
                                                    )}
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {allUsers.length === 0 && (
                            <div className="text-center py-12">
                                <div className="text-gray-500">
                                    {search ? t('telegram.users.noUsersFound') : t('telegram.users.noUserData')}
                                </div>
                            </div>
                        )}

                        {/* 分页信息 */}
                        {users?.meta && (
                            <div className="mt-6 flex items-center justify-between border-t border-gray-200 pt-6">
                                <div className="text-sm text-gray-700">
                                    {t('telegram.users.showing')} {users.meta.from || 0} {t('telegram.users.to')} {users.meta.to || 0} {t('telegram.users.of')} {users.meta.total || 0} {t('telegram.users.records')}
                                </div>
                                <div className="text-sm text-gray-500">
                                    {t('telegram.users.page')} {users.meta.current_page || 1} {t('telegram.users.ofPages')} {users.meta.last_page || 1} {t('telegram.users.page')}
                                </div>
                            </div>
                        )}
                    </Card>
                </div>
            </div>
        </>
    );
}
