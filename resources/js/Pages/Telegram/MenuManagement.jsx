import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button, Card, Input, Select, Textarea, Modal } from '@/Components/UI';
import {
    FaPlus,
    FaEdit,
    FaTrash,
    FaSave,
    FaTimes,
    FaEye,
    FaEyeSlash,
    FaArrowUp,
    FaArrowDown,
    FaImage,
    FaLanguage,
    FaChevronRight,
    FaChevronDown,
    FaHome
} from 'react-icons/fa';
import { toast } from 'react-hot-toast';

export default function MenuManagement({
    menuTree = [],
    languages = [],
    stats = {},
    allMenuItems = [],
    flash = {}
}) {
    const [expandedItems, setExpandedItems] = useState(new Set());
    const [editingItem, setEditingItem] = useState(null);
    const [showCreateForm, setShowCreateForm] = useState(false);
    const [selectedLanguage, setSelectedLanguage] = useState(languages[0]?.id || 1);
    const [draggedItem, setDraggedItem] = useState(null);
    const [formData, setFormData] = useState({
        parent_id: null,
        type: 'button',
        key: '',
        callback_data: '',
        url: '',
        sort_order: 0,
        is_active: true,
        show_in_menu: true,
        translations: {}
    });

    // 初始化翻译数据
    useEffect(() => {
        if (languages.length > 0 && Object.keys(formData.translations).length === 0) {
            const translations = {};
            languages.forEach(lang => {
                translations[lang.id] = {
                    title: '',
                    description: ''
                };
            });
            setFormData(prev => ({ ...prev, translations }));
        }
    }, [languages]);

    // 处理展开/折叠
    const toggleExpanded = (itemId) => {
        const newExpanded = new Set(expandedItems);
        if (newExpanded.has(itemId)) {
            newExpanded.delete(itemId);
        } else {
            newExpanded.add(itemId);
        }
        setExpandedItems(newExpanded);
    };

    // 处理编辑
    const handleEdit = (item) => {
        setEditingItem(item.id);
        setFormData({
            parent_id: item.parent_id,
            type: item.type,
            key: item.key,
            callback_data: item.callback_data || '',
            url: item.url || '',
            sort_order: item.sort_order,
            is_active: item.is_active,
            show_in_menu: item.show_in_menu,
            translations: item.translations || {}
        });
        setShowCreateForm(true);
    };

    // 处理删除
    const handleDelete = (itemId) => {
        if (confirm('确定要删除这个菜单项吗？这将同时删除所有子菜单项。')) {
            router.delete(`/telegram/menu/${itemId}`, {
                onSuccess: () => {
                    router.reload({ only: ['menuTree', 'stats'] });
                },
                onError: () => {
                    toast.error('删除失败');
                }
            });
        }
    };

    // 处理状态切换
    const toggleStatus = (itemId, currentStatus) => {
        router.patch(`/telegram/menu/${itemId}/toggle-status`, {
            is_active: !currentStatus
        }, {
            onSuccess: () => {
                router.reload({ only: ['menuTree'] });
            },
            onError: () => {
                toast.error('状态更新失败');
            }
        });
    };

    // 处理表单提交
    const handleSubmit = (e) => {
        e.preventDefault();

        const url = editingItem
            ? `/telegram/menu/${editingItem}`
            : '/telegram/menu';

        const method = editingItem ? 'patch' : 'post';

        router[method](url, formData, {
            onSuccess: () => {
                setShowCreateForm(false);
                setEditingItem(null);
                resetForm();
                router.reload({ only: ['menuTree', 'stats'] });
            },
            onError: (errors) => {
                console.error('表单错误:', errors);
                toast.error('操作失败，请检查表单数据');
            }
        });
    };

    // 重置表单
    const resetForm = () => {
        const translations = {};
        languages.forEach(lang => {
            translations[lang.id] = {
                title: '',
                description: ''
            };
        });

        setFormData({
            parent_id: null,
            type: 'button',
            key: '',
            callback_data: '',
            url: '',
            sort_order: 0,
            is_active: true,
            show_in_menu: true,
            translations
        });
    };

    // 处理拖拽开始
    const handleDragStart = (e, item) => {
        setDraggedItem(item);
        e.dataTransfer.effectAllowed = 'move';
    };

    // 处理拖拽结束
    const handleDragEnd = () => {
        setDraggedItem(null);
    };

    // 处理放置
    const handleDrop = (e, targetItem) => {
        e.preventDefault();

        if (!draggedItem || draggedItem.id === targetItem.id) {
            return;
        }

        // 更新排序
        router.patch('/telegram/menu/reorder', {
            moved_item_id: draggedItem.id,
            target_item_id: targetItem.id,
            position: 'after'
        }, {
            onSuccess: () => {
                router.reload({ only: ['menuTree'] });
            },
            onError: () => {
                toast.error('排序更新失败');
            }
        });
    };

    // 渲染菜单项
    const renderMenuItem = (item, level = 0) => {
        const hasChildren = item.children && item.children.length > 0;
        const isExpanded = expandedItems.has(item.id);
        const translation = item.translations?.[selectedLanguage] ||
                           Object.values(item.translations || {})[0] ||
                           { title: item.key, description: null };

        return (
            <div key={item.id} className="border border-gray-200 rounded-lg mb-2">
                <div
                    className={`p-4 flex items-center justify-between hover:bg-gray-50 ${
                        level > 0 ? 'ml-' + (level * 4) : ''
                    }`}
                    draggable
                    onDragStart={(e) => handleDragStart(e, item)}
                    onDragEnd={handleDragEnd}
                    onDragOver={(e) => e.preventDefault()}
                    onDrop={(e) => handleDrop(e, item)}
                >
                    <div className="flex items-center space-x-3 flex-1">
                        {hasChildren && (
                            <button
                                onClick={() => toggleExpanded(item.id)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                {isExpanded ? <FaChevronDown /> : <FaChevronRight />}
                            </button>
                        )}

                        {!hasChildren && level > 0 && (
                            <div className="w-4" />
                        )}

                        <div className="flex-1">
                            <div className="flex items-center space-x-2">
                                <h3 className="font-medium text-gray-900">
                                    {translation.title || item.key}
                                </h3>
                                <span className={`px-2 py-1 text-xs rounded-full ${
                                    item.type === 'button' ? 'bg-blue-100 text-blue-800' :
                                    item.type === 'url' ? 'bg-green-100 text-green-800' :
                                    item.type === 'callback' ? 'bg-purple-100 text-purple-800' :
                                    'bg-gray-100 text-gray-800'
                                }`}>
                                    {item.type}
                                </span>
                                {!item.is_active && (
                                    <span className="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">
                                        已禁用
                                    </span>
                                )}
                            </div>
                            {translation.description && (
                                <p className="text-sm text-gray-500 mt-1">
                                    {translation.description}
                                </p>
                            )}
                            <div className="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                <span>排序: {item.sort_order}</span>
                                <span>键: {item.key}</span>
                                {item.callback_data && (
                                    <span>回调: {item.callback_data}</span>
                                )}
                                {item.url && (
                                    <span>链接: {item.url}</span>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center space-x-2">
                        <button
                            onClick={() => toggleStatus(item.id, item.is_active)}
                            className={`p-2 rounded-md ${
                                item.is_active
                                    ? 'text-green-600 hover:bg-green-50'
                                    : 'text-red-600 hover:bg-red-50'
                            }`}
                            title={item.is_active ? '禁用' : '启用'}
                        >
                            {item.is_active ? <FaEye /> : <FaEyeSlash />}
                        </button>

                        <button
                            onClick={() => handleEdit(item)}
                            className="p-2 text-blue-600 hover:bg-blue-50 rounded-md"
                            title="编辑"
                        >
                            <FaEdit />
                        </button>

                        <button
                            onClick={() => handleDelete(item.id)}
                            className="p-2 text-red-600 hover:bg-red-50 rounded-md"
                            title="删除"
                        >
                            <FaTrash />
                        </button>
                    </div>
                </div>

                {hasChildren && isExpanded && (
                    <div className="border-t border-gray-200 bg-gray-50 p-2">
                        {item.children.map(child => renderMenuItem(child, level + 1))}
                    </div>
                )}
            </div>
        );
    };

    return (
        <>
            <Head title="菜单管理" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* 页面标题 */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">
                                    菜单管理
                                </h1>
                                <p className="mt-1 text-sm text-gray-500">
                                    管理Telegram Bot的动态菜单结构
                                </p>
                            </div>
                            <div className="flex items-center space-x-3">
                                <Select
                                    value={selectedLanguage}
                                    onChange={(e) => setSelectedLanguage(e.target.value)}
                                    className="w-32"
                                    options={languages.map(lang => ({
                                        value: lang.id,
                                        label: lang.name
                                    }))}
                                />
                                <Button
                                    onClick={() => {
                                        setShowCreateForm(true);
                                        setEditingItem(null);
                                        resetForm();
                                    }}
                                    className="flex items-center space-x-2"
                                >
                                    <FaPlus />
                                    <span>新建菜单项</span>
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* 统计信息 */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <Card padding="default">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-blue-600">
                                    {stats.total_items || 0}
                                </div>
                                <div className="text-sm text-gray-500">总菜单项</div>
                            </div>
                        </Card>
                        <Card padding="default">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-green-600">
                                    {stats.active_items || 0}
                                </div>
                                <div className="text-sm text-gray-500">活跃菜单项</div>
                            </div>
                        </Card>
                        <Card padding="default">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-purple-600">
                                    {stats.total_languages || 0}
                                </div>
                                <div className="text-sm text-gray-500">支持语言</div>
                            </div>
                        </Card>
                        <Card padding="default">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-orange-600">
                                    {stats.menu_clicks || 0}
                                </div>
                                <div className="text-sm text-gray-500">菜单点击</div>
                            </div>
                        </Card>
                    </div>

                    {/* 菜单树 */}
                    <Card title="菜单结构" padding="none">
                        <div className="p-6">
                            {menuTree.length > 0 ? (
                                <div className="space-y-2">
                                    {menuTree.map(item => renderMenuItem(item))}
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <FaHome className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">
                                        暂无菜单项
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        开始创建您的第一个菜单项
                                    </p>
                                    <div className="mt-6">
                                        <Button
                                            onClick={() => {
                                                setShowCreateForm(true);
                                                setEditingItem(null);
                                                resetForm();
                                            }}
                                            className="flex items-center space-x-2"
                                        >
                                            <FaPlus />
                                            <span>创建菜单项</span>
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </Card>

                    {/* 创建/编辑表单模态框 */}
                    {showCreateForm && (
                        <Modal
                            isOpen={showCreateForm}
                            onClose={() => {
                                setShowCreateForm(false);
                                setEditingItem(null);
                            }}
                            title={editingItem ? '编辑菜单项' : '创建菜单项'}
                            size="xl"
                            bodyClassName="max-h-[70vh]"
                        >
                            <form onSubmit={handleSubmit}>
                                <div className="space-y-4">
                                        {/* 基本信息 */}
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    菜单类型
                                                </label>
                                                <Select
                                                    value={formData.type}
                                                    onChange={(e) => setFormData(prev => ({ ...prev, type: e.target.value }))}
                                                    required
                                                    options={[
                                                        { value: 'button', label: '按钮' },
                                                        { value: 'url', label: '链接' },
                                                        { value: 'submenu', label: '子菜单' },
                                                        { value: 'callback', label: '回调' }
                                                    ]}
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    菜单键值
                                                </label>
                                                <Input
                                                    type="text"
                                                    value={formData.key}
                                                    onChange={(e) => setFormData(prev => ({ ...prev, key: e.target.value }))}
                                                    placeholder="menu_key"
                                                    required
                                                />
                                            </div>
                                        </div>

                                        {/* 父级菜单选择 */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                父级菜单
                                            </label>
                                            <Select
                                                value={formData.parent_id || ''}
                                                onChange={(e) => setFormData(prev => ({ ...prev, parent_id: e.target.value || null }))}
                                                options={[
                                                    { value: '', label: '无（根级菜单）' },
                                                    ...allMenuItems
                                                        .filter(item => item.id !== editingItem && item.type !== 'url') // 排除自己和URL类型
                                                        .map(item => ({
                                                            value: item.id,
                                                            label: `${item.title} (${item.key})`
                                                        }))
                                                ]}
                                            />
                                        </div>

                                        {(formData.type === 'button' || formData.type === 'callback' || formData.type === 'submenu') && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    回调数据
                                                </label>
                                                <Input
                                                    type="text"
                                                    value={formData.callback_data}
                                                    onChange={(e) => setFormData(prev => ({ ...prev, callback_data: e.target.value }))}
                                                    placeholder="callback_data"
                                                />
                                            </div>
                                        )}

                                        {formData.type === 'url' && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    链接地址
                                                </label>
                                                <Input
                                                    type="url"
                                                    value={formData.url}
                                                    onChange={(e) => setFormData(prev => ({ ...prev, url: e.target.value }))}
                                                    placeholder="https://example.com"
                                                />
                                            </div>
                                        )}

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    排序
                                                </label>
                                                <Input
                                                    type="number"
                                                    value={formData.sort_order}
                                                    onChange={(e) => setFormData(prev => ({ ...prev, sort_order: parseInt(e.target.value) }))}
                                                    min="0"
                                                />
                                            </div>

                                            <div className="flex items-center space-x-4 pt-6">
                                                <label className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        checked={formData.is_active}
                                                        onChange={(e) => setFormData(prev => ({ ...prev, is_active: e.target.checked }))}
                                                        className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                    />
                                                    <span className="ml-2 text-sm text-gray-700">启用</span>
                                                </label>
                                            </div>
                                        </div>

                                        {/* 多语言翻译 */}
                                        <div>
                                            <h4 className="text-sm font-medium text-gray-700 mb-3">多语言翻译</h4>
                                            <div className="space-y-4">
                                                {languages.map(lang => (
                                                    <div key={lang.id} className="border border-gray-200 rounded-lg p-4">
                                                        <h5 className="text-sm font-medium text-gray-600 mb-2">
                                                            {lang.name} ({lang.code})
                                                        </h5>
                                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <div>
                                                                <label className="block text-xs text-gray-500 mb-1">
                                                                    标题
                                                                </label>
                                                                <Input
                                                                    type="text"
                                                                    value={formData.translations[lang.id]?.title || ''}
                                                                    onChange={(e) => {
                                                                        const newTranslations = { ...formData.translations };
                                                                        if (!newTranslations[lang.id]) {
                                                                            newTranslations[lang.id] = {};
                                                                        }
                                                                        newTranslations[lang.id].title = e.target.value;
                                                                        setFormData(prev => ({ ...prev, translations: newTranslations }));
                                                                    }}
                                                                    placeholder="菜单标题"
                                                                />
                                                            </div>
                                                            <div>
                                                                <label className="block text-xs text-gray-500 mb-1">
                                                                    描述
                                                                </label>
                                                                <Textarea
                                                                    value={formData.translations[lang.id]?.description || ''}
                                                                    onChange={(e) => {
                                                                        const newTranslations = { ...formData.translations };
                                                                        if (!newTranslations[lang.id]) {
                                                                            newTranslations[lang.id] = {};
                                                                        }
                                                                        newTranslations[lang.id].description = e.target.value;
                                                                        setFormData(prev => ({ ...prev, translations: newTranslations }));
                                                                    }}
                                                                    placeholder="菜单描述"
                                                                    rows={2}
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                </div>

                                <div className="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setShowCreateForm(false);
                                            setEditingItem(null);
                                        }}
                                    >
                                        取消
                                    </Button>
                                    <Button type="submit" className="flex items-center space-x-2">
                                        <FaSave />
                                        <span>{editingItem ? '更新' : '创建'}</span>
                                    </Button>
                                </div>
                            </form>
                        </Modal>
                    )}
                </div>
            </div>
        </>
    );
}
