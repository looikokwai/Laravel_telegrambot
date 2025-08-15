import React, { useState, useEffect, useCallback } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { Button, Card, Input, Modal } from '@/Components/UI';
import { useTranslation } from 'react-i18next';
import {
    FaSearch,
    FaPlus,
    FaEdit,
    FaTrash,
    FaSpinner,
    FaUsers,
    FaToggleOn,
    FaToggleOff,
    FaPaperPlane,
} from 'react-icons/fa';

export default function GroupManagement({ groups = [] }) {
    const { t } = useTranslation();
    const [search, setSearch] = useState('');
    const [searchLoading, setSearchLoading] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [showAddModal, setShowAddModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [selectedGroup, setSelectedGroup] = useState(null);
    const [showMessageModal, setShowMessageModal] = useState(false);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        group_id: '',
        title: '',
        description: '',
        is_active: true
    });

    const messageForm = useForm({
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
                    router.get('/telegram/groups', { search: query }, {
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

    const handleAddGroup = (e) => {
        e.preventDefault();
        post('/telegram/groups', {
            onSuccess: () => {
                setShowAddModal(false);
                reset();
                // 刷新页面数据
                router.reload();
            },
        });
    };

    const handleEditGroup = (e) => {
        e.preventDefault();
        put(`/telegram/groups/${selectedGroup.id}`, {
            onSuccess: () => {
                setShowEditModal(false);
                setSelectedGroup(null);
                reset();
                // 刷新页面数据
                router.reload();
            },
        });
    };

    const handleDeleteGroup = () => {
        router.delete(`/telegram/groups/${selectedGroup.id}`, {
            onSuccess: () => {
                setShowDeleteModal(false);
                setSelectedGroup(null);
                // 刷新页面数据
                router.reload();
            },
        });
    };

    const handleToggleStatus = (groupId, currentStatus) => {
        // 使用 router.put 因为这不是表单数据，而是简单的状态切换
        router.put(`/telegram/groups/${groupId}/toggle-status`, {
            is_active: !currentStatus
        }, {
            onSuccess: () => {
                // 刷新页面数据
                router.reload();
            },
        });
    };

    const handleSendMessage = (e) => {
        e.preventDefault();
        messageForm.post(`/telegram/groups/${selectedGroup.id}/send-message`, {
            onSuccess: () => {
                setShowMessageModal(false);
                setSelectedGroup(null);
                messageForm.reset();
                // 刷新页面数据
                router.reload();
            },
        });
    };

    const openEditModal = (group) => {
        setSelectedGroup(group);
        setData({
            group_id: group.group_id,
            title: group.title,
            description: group.description || '',
            is_active: group.is_active
        });
        setShowEditModal(true);
    };

    const openDeleteModal = (group) => {
        setSelectedGroup(group);
        setShowDeleteModal(true);
    };

    const openMessageModal = (group) => {
        setSelectedGroup(group);
        setShowMessageModal(true);
    };

    // 安全地处理群组数据
    const allGroups = groups || [];

    const formatDate = (dateString) => {
        if (!dateString) return t('telegram.users.never');
        return new Date(dateString).toLocaleDateString();
    };

    const getStatusBadge = (isActive) => {
        return isActive ? (
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                {t('telegram.users.active')}
            </span>
        ) : (
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                {t('telegram.users.inactive')}
            </span>
        );
    };

    return (
        <>
            <Head title={t('telegram.groups.groupManagement')} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6 flex justify-between items-center">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{t('telegram.groups.groupManagement')}</h1>
                            <p className="mt-1 text-sm text-gray-500">
                                {t('telegram.groups.groupManagementSubtitle')} ({allGroups.length} {t('telegram.groups.foundGroups')})
                            </p>
                        </div>
                        <Button
                            onClick={() => setShowAddModal(true)}
                            className="flex items-center space-x-2"
                        >
                            <FaPlus className="w-4 h-4" />
                            <span>{t('telegram.groups.addGroup')}</span>
                        </Button>
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
                                    placeholder={t('telegram.groups.searchPlaceholder')}
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                    disabled={searchLoading}
                                />
                            </div>
                            {search && (
                                <div className="text-sm text-gray-500">
                                    {t('telegram.groups.searchResults')}: "{search}" - {t('telegram.groups.foundGroups')} {allGroups.length}
                                </div>
                            )}
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {t('telegram.groups.groupName')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {t('telegram.groups.groupType')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {t('telegram.groups.memberCount')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {t('telegram.groups.status')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {t('telegram.groups.lastActivity')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {t('telegram.groups.operations')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {allGroups.length === 0 ? (
                                        <tr>
                                            <td colSpan="6" className="px-6 py-4 text-center text-gray-500">
                                                {t('telegram.groups.noGroupsFound')}
                                            </td>
                                        </tr>
                                    ) : (
                                        allGroups.map((group) => (
                                            <tr key={group.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        <div className="flex-shrink-0 h-10 w-10">
                                                            <div className="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                                <FaUsers className="h-5 w-5 text-blue-600" />
                                                            </div>
                                                        </div>
                                                        <div className="ml-4">
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {group.title || t('telegram.users.unknown')}
                                                            </div>
                                                            <div className="text-sm text-gray-500">
                                                                {group.group_id}
                                                            </div>
                                                            {group.username && (
                                                                <div className="text-sm text-gray-400">
                                                                    @{group.username}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {group.type === 'supergroup' ? t('telegram.groups.supergroup') : t('telegram.groups.group')}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {group.member_count || 0}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {getStatusBadge(group.is_active)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {formatDate(group.last_activity)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div className="flex items-center space-x-2">
                                                        <button
                                                            onClick={() => openMessageModal(group)}
                                                            className="text-blue-600 hover:text-blue-900"
                                                            title={t('telegram.groups.sendMessage')}
                                                        >
                                                            <FaPaperPlane className="w-4 h-4" />
                                                        </button>
                                                        <button
                                                            onClick={() => openEditModal(group)}
                                                            className="text-indigo-600 hover:text-indigo-900"
                                                            title={t('telegram.groups.editGroup')}
                                                        >
                                                            <FaEdit className="w-4 h-4" />
                                                        </button>
                                                        <button
                                                            onClick={() => handleToggleStatus(group.id, group.is_active)}
                                                            className={`${group.is_active ? 'text-green-600 hover:text-green-900' : 'text-gray-600 hover:text-gray-900'}`}
                                                            title={group.is_active ? t('telegram.groups.disable') : t('telegram.groups.enable')}
                                                        >
                                                            {group.is_active ? <FaToggleOn className="w-4 h-4" /> : <FaToggleOff className="w-4 h-4" />}
                                                        </button>
                                                        <button
                                                            onClick={() => openDeleteModal(group)}
                                                            className="text-red-600 hover:text-red-900"
                                                            title={t('telegram.groups.deleteGroup')}
                                                        >
                                                            <FaTrash className="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            </div>

            {/* 添加群组模态框 */}
            <Modal
                isOpen={showAddModal}
                onClose={() => setShowAddModal(false)}
                title={t('telegram.groups.addGroup')}
            >
                <form onSubmit={handleAddGroup} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">
                            {t('telegram.groups.groupId')}
                        </label>
                        <Input
                            type="text"
                            value={data.group_id}
                            onChange={(e) => setData('group_id', e.target.value)}
                            placeholder={t('telegram.groups.groupIdPlaceholder')}
                            required
                        />
                        {errors.group_id && <p className="mt-1 text-sm text-red-600">{errors.group_id}</p>}
                    </div>
                    <div className="flex justify-end space-x-3">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setShowAddModal(false)}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <FaSpinner className="w-4 h-4 animate-spin" /> : t('common.save')}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* 编辑群组模态框 */}
            <Modal
                isOpen={showEditModal}
                onClose={() => setShowEditModal(false)}
                title={t('telegram.groups.editGroup')}
            >
                <form onSubmit={handleEditGroup} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">
                            {t('telegram.groups.groupName')}
                        </label>
                        <Input
                            type="text"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            required
                        />
                        {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700">
                            {t('telegram.groups.groupDescription')}
                        </label>
                        <textarea
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            rows={3}
                        />
                        {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                    </div>
                    <div className="flex items-center">
                        <input
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) => setData('is_active', e.target.checked)}
                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label className="ml-2 block text-sm text-gray-900">
                            {t('telegram.groups.isActive')}
                        </label>
                    </div>
                    <div className="flex justify-end space-x-3">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setShowEditModal(false)}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <FaSpinner className="w-4 h-4 animate-spin" /> : t('common.save')}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* 删除群组模态框 */}
            <Modal
                isOpen={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                title={t('telegram.groups.deleteGroup')}
            >
                <div className="space-y-4">
                    <p className="text-sm text-gray-500">
                        {t('common.confirm')} {t('telegram.groups.deleteGroup').toLowerCase()} "{selectedGroup?.title}"?
                    </p>
                    <div className="flex justify-end space-x-3">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setShowDeleteModal(false)}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button
                            type="button"
                            variant="danger"
                            onClick={handleDeleteGroup}
                            disabled={processing}
                        >
                            {processing ? <FaSpinner className="w-4 h-4 animate-spin" /> : t('common.delete')}
                        </Button>
                    </div>
                </div>
            </Modal>

            {/* 发送消息模态框 */}
            <Modal
                isOpen={showMessageModal}
                onClose={() => setShowMessageModal(false)}
                title={t('telegram.groups.sendMessage')}
                size="lg"
            >
                <form onSubmit={handleSendMessage} className="space-y-6">
                    {/* 消息文本 */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            {t('telegram.groups.messageText')}
                        </label>
                        <textarea
                            value={messageForm.data.message}
                            onChange={(e) => messageForm.setData('message', e.target.value)}
                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            rows={4}
                            placeholder={t('telegram.groupBroadcast.enterMessage')}
                            required
                        />
                        {messageForm.errors.message && <p className="mt-1 text-sm text-red-600">{messageForm.errors.message}</p>}
                    </div>

                    {/* 图片上传 */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            {t('telegram.groups.attachImage')} ({t('common.optional')})
                        </label>
                        <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                            <div className="space-y-1 text-center">
                                <svg className="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                                <div className="flex text-sm text-gray-600">
                                    <label htmlFor="message-image" className="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>{t('telegram.groups.uploadImage')}</span>
                                        <input
                                            id="message-image"
                                            name="image"
                                            type="file"
                                            className="sr-only"
                                            accept="image/*"
                                            onChange={(e) => messageForm.setData('image', e.target.files[0])}
                                        />
                                    </label>
                                    <p className="pl-1">{t('telegram.groups.orDragDrop')}</p>
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
                            {t('telegram.groups.inlineKeyboard')} ({t('common.optional')})
                        </label>
                        <div className="space-y-2">
                            {messageForm.data.keyboard.map((row, rowIndex) => (
                                <div key={rowIndex} className="flex items-center space-x-2">
                                    <div className="flex-1 grid grid-cols-1 gap-2">
                                        {row.map((button, buttonIndex) => (
                                            <div key={buttonIndex} className="flex space-x-2">
                                                <input
                                                    type="text"
                                                    placeholder={t('telegram.groups.buttonText')}
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
                                                    placeholder={t('telegram.groups.buttonUrl')}
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
                                    {t('telegram.groups.addButton')}
                                </Button>
                                {messageForm.data.keyboard.length > 0 && (
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        size="sm"
                                        onClick={() => messageForm.setData('keyboard', [])}
                                    >
                                        {t('telegram.groups.clearKeyboard')}
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
                            {t('telegram.groups.sendMessage')}
                        </Button>
                    </div>
                </form>
            </Modal>
        </>
    );
}
