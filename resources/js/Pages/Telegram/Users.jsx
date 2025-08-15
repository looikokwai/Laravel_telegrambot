import React, { useState, useEffect, useCallback } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { Button, Card, Input, Modal } from '@/Components/UI';
import { useTranslation } from 'react-i18next';
import { FaSearch, FaUserCheck, FaUserTimes, FaSpinner, FaPaperPlane, FaPlus, FaTrash } from 'react-icons/fa';

export default function TelegramUsers({ users }) {
    const { t } = useTranslation();
    const [search, setSearch] = useState('');
    const [searchLoading, setSearchLoading] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [showMessageModal, setShowMessageModal] = useState(false);
    const [selectedUser, setSelectedUser] = useState(null);

    const { post } = useForm();
    
    const messageForm = useForm({
        user_id: null,
        message: '',
        image: null,
        keyboard: []
    });

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

    const handleSendMessage = (e) => {
        e.preventDefault();
        
        // 创建 FormData 对象
        const formData = new FormData();
        formData.append('user_id', selectedUser.id);
        formData.append('message', messageForm.data.message);
        
        if (messageForm.data.image) {
            formData.append('image', messageForm.data.image);
        }
        
        if (messageForm.data.keyboard && messageForm.data.keyboard.length > 0) {
            formData.append('keyboard', JSON.stringify(messageForm.data.keyboard));
        }
        
        router.post('/telegram/send-message', formData, {
            onSuccess: () => {
                setShowMessageModal(false);
                setSelectedUser(null);
                messageForm.reset();
            },
            onError: (errors) => {
                console.error('Send message errors:', errors);
            }
        });
    };

    const openMessageModal = (user) => {
        setSelectedUser(user);
        setShowMessageModal(true);
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
                                                <div className="flex items-center space-x-2">
                                                    <button
                                                        onClick={() => openMessageModal(user)}
                                                        className="text-blue-600 hover:text-blue-900"
                                                        title={t('telegram.users.sendMessage')}
                                                    >
                                                        <FaPaperPlane className="w-4 h-4" />
                                                    </button>
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
                                                </div>
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

            {/* 发送消息模态框 */}
            <Modal
                isOpen={showMessageModal}
                onClose={() => setShowMessageModal(false)}
                title={t('telegram.users.sendMessage')}
                size="lg"
            >
                <form onSubmit={handleSendMessage} className="space-y-6">
                    {/* 用户信息 */}
                    {selectedUser && (
                        <div className="bg-gray-50 p-4 rounded-md">
                            <div className="flex items-center">
                                <div className="flex-shrink-0 h-10 w-10">
                                    <div className="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span className="text-sm font-medium text-gray-700">
                                            {(selectedUser.first_name || 'U')[0].toUpperCase()}
                                        </span>
                                    </div>
                                </div>
                                <div className="ml-4">
                                    <div className="text-sm font-medium text-gray-900">
                                        {selectedUser.first_name || t('telegram.users.unknown')}
                                    </div>
                                    <div className="text-sm text-gray-500">
                                        @{selectedUser.username || t('telegram.users.noUsername')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* 消息文本 */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            {t('telegram.users.messageText')}
                        </label>
                        <textarea
                            value={messageForm.data.message}
                            onChange={(e) => messageForm.setData('message', e.target.value)}
                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            rows={4}
                            placeholder={t('telegram.users.enterMessage')}
                            required
                        />
                        {messageForm.errors.message && <p className="mt-1 text-sm text-red-600">{messageForm.errors.message}</p>}
                    </div>

                    {/* 图片上传 */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            {t('telegram.users.attachImage')} ({t('common.optional')})
                        </label>
                        <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                            <div className="space-y-1 text-center">
                                <svg className="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                                <div className="flex text-sm text-gray-600">
                                    <label htmlFor="user-message-image" className="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>{t('telegram.users.uploadImage')}</span>
                                        <input
                                            id="user-message-image"
                                            name="image"
                                            type="file"
                                            className="sr-only"
                                            accept="image/*"
                                            onChange={(e) => messageForm.setData('image', e.target.files[0])}
                                        />
                                    </label>
                                    <p className="pl-1">{t('telegram.users.orDragDrop')}</p>
                                </div>
                                <p className="text-xs text-gray-500">
                                    PNG, JPG, GIF {t('common.upTo')} 5MB
                                </p>
                            </div>
                        </div>
                        {messageForm.data.image && (
                            <div className="mt-2 flex items-center justify-between bg-gray-50 px-3 py-2 rounded-md">
                                <span className="text-sm text-gray-700">{messageForm.data.image.name}</span>
                                <button
                                    type="button"
                                    onClick={() => messageForm.setData('image', null)}
                                    className="text-red-600 hover:text-red-800"
                                >
                                    <FaTrash className="w-4 h-4" />
                                </button>
                            </div>
                        )}
                        {messageForm.errors.image && <p className="mt-1 text-sm text-red-600">{messageForm.errors.image}</p>}
                    </div>

                    {/* 内联键盘 */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            {t('telegram.users.inlineKeyboard')} ({t('common.optional')})
                        </label>
                        <div className="space-y-2">
                            {messageForm.data.keyboard.map((row, rowIndex) => (
                                <div key={rowIndex} className="flex items-center space-x-2">
                                    <div className="flex-1 grid grid-cols-1 gap-2">
                                        {row.map((button, buttonIndex) => (
                                            <div key={buttonIndex} className="flex space-x-2">
                                                <input
                                                    type="text"
                                                    placeholder={t('telegram.users.buttonText')}
                                                    value={button.text || ''}
                                                    onChange={(e) => {
                                                        const newKeyboard = [...messageForm.data.keyboard];
                                                        newKeyboard[rowIndex][buttonIndex].text = e.target.value;
                                                        messageForm.setData('keyboard', newKeyboard);
                                                    }}
                                                    className="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                />
                                                <input
                                                    type="text"
                                                    placeholder={t('telegram.users.buttonUrl')}
                                                    value={button.url || ''}
                                                    onChange={(e) => {
                                                        const newKeyboard = [...messageForm.data.keyboard];
                                                        newKeyboard[rowIndex][buttonIndex].url = e.target.value;
                                                        messageForm.setData('keyboard', newKeyboard);
                                                    }}
                                                    className="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        const newKeyboard = [...messageForm.data.keyboard];
                                                        newKeyboard[rowIndex].splice(buttonIndex, 1);
                                                        if (newKeyboard[rowIndex].length === 0) {
                                                            newKeyboard.splice(rowIndex, 1);
                                                        }
                                                        messageForm.setData('keyboard', newKeyboard);
                                                    }}
                                                    className="text-red-600 hover:text-red-800"
                                                >
                                                    <FaTrash className="w-4 h-4" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                            <div className="flex space-x-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    onClick={() => {
                                        const newKeyboard = [...messageForm.data.keyboard];
                                        newKeyboard.push([{ text: '', url: '' }]);
                                        messageForm.setData('keyboard', newKeyboard);
                                    }}
                                >
                                    <FaPlus className="w-4 h-4 mr-1" />
                                    {t('telegram.users.addButton')}
                                </Button>
                                {messageForm.data.keyboard.length > 0 && (
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        size="sm"
                                        onClick={() => messageForm.setData('keyboard', [])}
                                    >
                                        {t('telegram.users.clearKeyboard')}
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-end space-x-3 pt-4 border-t">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => {
                                setShowMessageModal(false);
                                messageForm.reset();
                            }}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button type="submit" disabled={messageForm.processing}>
                            {messageForm.processing ? <FaSpinner className="w-4 h-4 animate-spin mr-2" /> : <FaPaperPlane className="w-4 h-4 mr-2" />}
                            {t('telegram.users.sendMessage')}
                        </Button>
                    </div>
                </form>
            </Modal>
        </>
    );
}
