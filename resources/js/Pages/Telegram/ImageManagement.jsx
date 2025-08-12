import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Button, Card, Input, Select, Modal } from '@/Components/UI';
import {
    FaTrash,
    FaDownload,
    FaEye,
    FaUpload,
    FaImage,
    FaSearch,
    FaTimes,
    FaLink,
    FaUnlink,
    FaCompress
} from 'react-icons/fa';
import { toast } from 'react-hot-toast';

export default function ImageManagement({
    images = [],
    menuItems = [],
    stats = {},
    filters = {},
    languages = []
}) {
    const [selectedImages, setSelectedImages] = useState(new Set());
    const [showUploadModal, setShowUploadModal] = useState(false);
    const [showPreviewModal, setShowPreviewModal] = useState(false);
    const [previewImage, setPreviewImage] = useState(null);
    const [showLinkModal, setShowLinkModal] = useState(false);
    const [linkingImage, setLinkingImage] = useState(null);
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [mimeTypeFilter, setMimeTypeFilter] = useState(filters.mime_type || '');
    const [sizeFilter, setSizeFilter] = useState(filters.size || '');
    const [uploadFiles, setUploadFiles] = useState([]);
    const [uploadProgress, setUploadProgress] = useState({});
    const [isUploading, setIsUploading] = useState(false);

    const { data: linkForm, setData: setLinkForm, post: postLink, processing: isLinking, errors: linkErrors, reset: resetLink } = useForm({
        menu_item_id: '',
        image_id: null,
        language_id: '',
        type: 'icon'
    });

    // 处理文件选择
    const handleFileSelect = (e) => {
        const files = Array.from(e.target.files);
        const validFiles = files.filter(file => {
            const isValidType = file.type.startsWith('image/');
            const isValidSize = file.size <= 10 * 1024 * 1024; // 10MB

            if (!isValidType) {
                toast.error(`${file.name} 不是有效的图片文件`);
                return false;
            }
            if (!isValidSize) {
                toast.error(`${file.name} 文件大小超过10MB`);
                return false;
            }
            return true;
        });

        setUploadFiles(validFiles);
        if (validFiles.length > 0) {
            setShowUploadModal(true);
        }
    };

    // 处理文件上传
    const handleUpload = async () => {
        if (uploadFiles.length === 0) return;

        setIsUploading(true);

        // 使用 Promise.all 来并行处理所有文件上传
        const uploadPromises = uploadFiles.map(file => {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('alt_text', file.name.split('.')[0]);

            return new Promise((resolve, reject) => {
                router.post('/telegram/images/upload', formData, {
                    onProgress: (progress) => {
                        setUploadProgress(prev => ({
                            ...prev,
                            [file.name]: progress.percentage
                        }));
                    },
                    onSuccess: () => resolve(),
                    onError: (errors) => reject(errors),
                    preserveState: true, // 避免在每次上传时重置页面状态
                });
            });
        });

        try {
            await Promise.all(uploadPromises);
        } catch (error) {
            // 错误消息将由全局 flash handler 处理
            console.error('上传错误:', error);
        } finally {
            setIsUploading(false);
            setShowUploadModal(false);
            setUploadFiles([]);
            setUploadProgress({});
            // 所有文件处理完毕后，刷新一次数据
            router.reload({ only: ['images', 'stats'] });
        }
    };

    // 处理图片删除
    const handleDelete = (imageId) => {
        if (confirm('确定要删除这张图片吗？这将同时删除所有关联的菜单项。')) {
            router.delete(`/telegram/images/${imageId}`, {
                onSuccess: () => {
                    toast.success('图片删除成功');
                },
                onError: () => {
                    toast.error('删除失败');
                }
            });
        }
    };

    // 处理批量删除
    const handleBatchDelete = () => {
        if (selectedImages.size === 0) {
            toast.error('请选择要删除的图片');
            return;
        }

        if (confirm(`确定要删除选中的 ${selectedImages.size} 张图片吗？`)) {
            router.post('/telegram/images/batch-delete', {
                data: { image_ids: Array.from(selectedImages) },
                onSuccess: () => {
                    toast.success('批量删除成功');
                    setSelectedImages(new Set());
                },
                onError: () => {
                    toast.error('批量删除失败');
                }
            });
        }
    };

    // 处理图片预览
    const handlePreview = (image) => {
        setPreviewImage(image);
        setShowPreviewModal(true);
    };

    // 处理关联菜单项
    const handleLinkToMenu = (image) => {
        setLinkingImage(image);
        setShowLinkModal(true);
    };

    // 处理取消关联
    const handleUnlinkFromMenu = (imageId, menuItemId) => {
        router.post('/telegram/images/detach-from-menu', {
            image_id: imageId,
            menu_item_id: menuItemId
        }, {
            onSuccess: () => {
                toast.success('取消关联成功');
            },
            onError: () => {
                toast.error('取消关联失败');
            }
        });
    };

    // 处理图片优化
    const handleOptimize = (imageId) => {
        router.post(`/telegram/images/${imageId}/optimize`, {}, {
            onSuccess: () => {
                toast.success('图片优化成功');
            },
            onError: () => {
                toast.error('图片优化失败');
            }
        });
    };

    // 处理搜索和筛选
    const handleSearch = () => {
        const params = new URLSearchParams();
        if (searchTerm) params.append('search', searchTerm);
        if (mimeTypeFilter) params.append('mime_type', mimeTypeFilter);
        if (sizeFilter) params.append('size', sizeFilter);

        router.get(`/telegram/images?${params.toString()}`);
    };

    // 处理全选
    const handleSelectAll = () => {
        if (selectedImages.size === images.length) {
            setSelectedImages(new Set());
        } else {
            setSelectedImages(new Set(images.map(img => img.id)));
        }
    };

    // 格式化文件大小
    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    return (
        <>
            <Head title="图片管理" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* 页面标题 */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">
                                    图片管理
                                </h1>
                                <p className="mt-1 text-sm text-gray-500">
                                    管理Telegram Bot菜单中使用的图片资源
                                </p>
                            </div>
                            <div className="flex items-center space-x-3">
                                <input
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    onChange={handleFileSelect}
                                    className="hidden"
                                    id="image-upload"
                                />
                                <label
                                    htmlFor="image-upload"
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 cursor-pointer"
                                >
                                    <FaUpload className="mr-2" />
                                    上传图片
                                </label>
                                {selectedImages.size > 0 && (
                                    <Button
                                        onClick={handleBatchDelete}
                                        variant="danger"
                                        className="flex items-center space-x-2"
                                    >
                                        <FaTrash />
                                        <span>删除选中 ({selectedImages.size})</span>
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* 统计信息 */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <Card padding="default">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-blue-600">
                                    {stats.total_images || 0}
                                </div>
                                <div className="text-sm text-gray-500">总图片数</div>
                            </div>
                        </Card>
                        <Card padding="default">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-green-600">
                                    {stats.associated_images || 0}
                                </div>
                                <div className="text-sm text-gray-500">已关联图片</div>
                            </div>
                        </Card>
                        <Card padding="default">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-purple-600">
                                    {formatFileSize(stats.total_size || 0)}
                                </div>
                                <div className="text-sm text-gray-500">总存储大小</div>
                            </div>
                        </Card>
                        <Card padding="default">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-orange-600">
                                    {stats.optimized_images || 0}
                                </div>
                                <div className="text-sm text-gray-500">已优化图片</div>
                            </div>
                        </Card>
                    </div>

                    {/* 搜索和筛选 */}
                    <Card title="搜索和筛选" padding="default" className="mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <Input
                                    type="text"
                                    placeholder="搜索图片名称或描述..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                />
                            </div>
                            <div>
                                <Select
                                    value={mimeTypeFilter}
                                    onChange={(e) => setMimeTypeFilter(e.target.value)}
                                >
                                    <option value="">所有格式</option>
                                    <option value="image/jpeg">JPEG</option>
                                    <option value="image/png">PNG</option>
                                    <option value="image/gif">GIF</option>
                                    <option value="image/webp">WebP</option>
                                </Select>
                            </div>
                            <div>
                                <Select
                                    value={sizeFilter}
                                    onChange={(e) => setSizeFilter(e.target.value)}
                                >
                                    <option value="">所有大小</option>
                                    <option value="small">小于1MB</option>
                                    <option value="medium">1-5MB</option>
                                    <option value="large">大于5MB</option>
                                </Select>
                            </div>
                            <div>
                                <Button
                                    onClick={handleSearch}
                                    className="w-full flex items-center justify-center space-x-2"
                                >
                                    <FaSearch />
                                    <span>搜索</span>
                                </Button>
                            </div>
                        </div>
                    </Card>

                    {/* 图片列表 */}
                    <Card title="图片列表" padding="none">
                        <div className="p-6">
                            {images.length > 0 ? (
                                <>
                                    {/* 批量操作栏 */}
                                    <div className="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                                        <label className="flex items-center">
                                            <input
                                                type="checkbox"
                                                checked={selectedImages.size === images.length && images.length > 0}
                                                onChange={handleSelectAll}
                                                className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                            />
                                            <span className="ml-2 text-sm text-gray-700">
                                                全选 ({selectedImages.size}/{images.length})
                                            </span>
                                        </label>

                                        {selectedImages.size > 0 && (
                                            <div className="flex items-center space-x-2">
                                                <Button
                                                    onClick={handleBatchDelete}
                                                    variant="danger"
                                                    size="sm"
                                                >
                                                    批量删除
                                                </Button>
                                            </div>
                                        )}
                                    </div>

                                    {/* 图片网格 */}
                                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                        {images.map(image => (
                                            <div key={image.id} className="relative group border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                                                {/* 选择框 */}
                                                <div className="absolute top-2 left-2 z-10">
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedImages.has(image.id)}
                                                        onChange={(e) => {
                                                            const newSelected = new Set(selectedImages);
                                                            if (e.target.checked) {
                                                                newSelected.add(image.id);
                                                            } else {
                                                                newSelected.delete(image.id);
                                                            }
                                                            setSelectedImages(newSelected);
                                                        }}
                                                        className="rounded border-gray-300 text-blue-600 shadow-sm"
                                                    />
                                                </div>

                                                {/* 图片 */}
                                                <div className="aspect-square bg-gray-100 relative overflow-hidden">
                                                    <img
                                                        src={image.thumbnail_url || image.url}
                                                        alt={image.alt_text}
                                                        className="w-full h-full object-cover cursor-pointer"
                                                        onClick={() => handlePreview(image)}
                                                    />

                                                    {/* 悬停操作按钮 */}
                                                    <div className="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-2">
                                                        <button
                                                            onClick={() => handlePreview(image)}
                                                            className="p-2 bg-white rounded-full text-gray-700 hover:text-blue-600"
                                                            title="预览"
                                                        >
                                                            <FaEye />
                                                        </button>
                                                        <button
                                                            onClick={() => handleLinkToMenu(image)}
                                                            className="p-2 bg-white rounded-full text-gray-700 hover:text-green-600"
                                                            title="关联菜单"
                                                        >
                                                            <FaLink />
                                                        </button>
                                                        <button
                                                            onClick={() => handleOptimize(image.id)}
                                                            className="p-2 bg-white rounded-full text-gray-700 hover:text-purple-600"
                                                            title="优化图片"
                                                        >
                                                            <FaCompress />
                                                        </button>
                                                        <button
                                                            onClick={() => handleDelete(image.id)}
                                                            className="p-2 bg-white rounded-full text-gray-700 hover:text-red-600"
                                                            title="删除"
                                                        >
                                                            <FaTrash />
                                                        </button>
                                                    </div>
                                                </div>

                                                {/* 图片信息 */}
                                                <div className="p-3">
                                                    <h3 className="text-sm font-medium text-gray-900 truncate" title={image.alt_text}>
                                                        {image.alt_text}
                                                    </h3>
                                                    <div className="mt-1 text-xs text-gray-500 space-y-1">
                                                        <div>{formatFileSize(image.file_size)}</div>
                                                        <div>{image.width} × {image.height}</div>
                                                        <div className="uppercase">{image.mime_type.split('/')[1]}</div>
                                                    </div>

                                                    {/* 关联的菜单项 */}
                                                    {image.menu_items && image.menu_items.length > 0 && (
                                                        <div className="mt-2">
                                                            <div className="text-xs text-gray-500 mb-1">关联菜单:</div>
                                                            <div className="flex flex-wrap gap-1">
                                                                {image.menu_items.slice(0, 2).map(menuItem => (
                                                                    <span
                                                                        key={menuItem.id}
                                                                        className="inline-flex items-center px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded"
                                                                    >
                                                                        {menuItem.key}
                                                                        <button
                                                                            onClick={() => handleUnlinkFromMenu(image.id, menuItem.id)}
                                                                            className="ml-1 text-blue-600 hover:text-blue-800"
                                                                        >
                                                                            <FaTimes className="w-2 h-2" />
                                                                        </button>
                                                                    </span>
                                                                ))}
                                                                {image.menu_items.length > 2 && (
                                                                    <span className="text-xs text-gray-500">
                                                                        +{image.menu_items.length - 2} 更多
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </>
                            ) : (
                                <div className="text-center py-12">
                                    <FaImage className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">
                                        暂无图片
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        开始上传您的第一张图片
                                    </p>
                                    <div className="mt-6">
                                        <label
                                            htmlFor="image-upload-empty"
                                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 cursor-pointer"
                                        >
                                            <FaUpload className="mr-2" />
                                            上传图片
                                        </label>
                                        <input
                                            type="file"
                                            multiple
                                            accept="image/*"
                                            onChange={handleFileSelect}
                                            className="hidden"
                                            id="image-upload-empty"
                                        />
                                    </div>
                                </div>
                            )}
                        </div>
                    </Card>

                    {/* 上传模态框 */}
                    {showUploadModal && (
                        <Modal
                            isOpen={showUploadModal}
                            onClose={() => !isUploading && setShowUploadModal(false)}
                            title="上传图片"
                        >
                            <div className="space-y-4">
                                <div className="text-sm text-gray-600">
                                    准备上传 {uploadFiles.length} 个文件
                                </div>

                                <div className="space-y-2 max-h-60 overflow-y-auto">
                                    {uploadFiles.map((file, index) => (
                                        <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div className="flex-1">
                                                <div className="text-sm font-medium text-gray-900">
                                                    {file.name}
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    {formatFileSize(file.size)}
                                                </div>
                                                {uploadProgress[file.name] && (
                                                    <div className="mt-1">
                                                        <div className="bg-gray-200 rounded-full h-2">
                                                            <div
                                                                className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                                style={{ width: `${uploadProgress[file.name]}%` }}
                                                            />
                                                        </div>
                                                        <div className="text-xs text-gray-500 mt-1">
                                                            {uploadProgress[file.name]}%
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                            {!isUploading && (
                                                <button
                                                    onClick={() => {
                                                        const newFiles = uploadFiles.filter((_, i) => i !== index);
                                                        setUploadFiles(newFiles);
                                                    }}
                                                    className="ml-2 text-red-600 hover:text-red-800"
                                                >
                                                    <FaTimes />
                                                </button>
                                            )}
                                        </div>
                                    ))}
                                </div>

                                <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                                    <Button
                                        variant="outline"
                                        onClick={() => setShowUploadModal(false)}
                                        disabled={isUploading}
                                    >
                                        取消
                                    </Button>
                                    <Button
                                        onClick={handleUpload}
                                        disabled={isUploading || uploadFiles.length === 0}
                                        className="flex items-center space-x-2"
                                    >
                                        <FaUpload />
                                        <span>{isUploading ? '上传中...' : '开始上传'}</span>
                                    </Button>
                                </div>
                            </div>
                        </Modal>
                    )}

                    {/* 预览模态框 */}
                    {showPreviewModal && previewImage && (
                        <Modal
                            isOpen={showPreviewModal}
                            onClose={() => setShowPreviewModal(false)}
                            title="图片预览"
                            size="large"
                        >
                            <div className="space-y-4">
                                <div className="text-center">
                                    <img
                                        src={previewImage.url}
                                        alt={previewImage.alt_text}
                                        className="max-w-full max-h-96 mx-auto rounded-lg shadow-lg"
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span className="font-medium text-gray-700">文件名:</span>
                                        <span className="ml-2 text-gray-900">{previewImage.original_name}</span>
                                    </div>
                                    <div>
                                        <span className="font-medium text-gray-700">文件大小:</span>
                                        <span className="ml-2 text-gray-900">{formatFileSize(previewImage.file_size)}</span>
                                    </div>
                                    <div>
                                        <span className="font-medium text-gray-700">尺寸:</span>
                                        <span className="ml-2 text-gray-900">{previewImage.width} × {previewImage.height}</span>
                                    </div>
                                    <div>
                                        <span className="font-medium text-gray-700">格式:</span>
                                        <span className="ml-2 text-gray-900 uppercase">{previewImage.mime_type}</span>
                                    </div>
                                    <div>
                                        <span className="font-medium text-gray-700">上传时间:</span>
                                        <span className="ml-2 text-gray-900">{new Date(previewImage.created_at).toLocaleString()}</span>
                                    </div>
                                    <div>
                                        <span className="font-medium text-gray-700">描述:</span>
                                        <span className="ml-2 text-gray-900">{previewImage.alt_text}</span>
                                    </div>
                                </div>

                                <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                                    <Button
                                        variant="outline"
                                        onClick={() => setShowPreviewModal(false)}
                                    >
                                        关闭
                                    </Button>
                                    <a
                                        href={previewImage.url}
                                        download={previewImage.original_name}
                                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                    >
                                        <FaDownload className="mr-2" />
                                        下载
                                    </a>
                                </div>
                            </div>
                        </Modal>
                    )}

                    {/* 关联菜单模态框 */}
                    {showLinkModal && linkingImage && (
                        <Modal
                            isOpen={showLinkModal}
                            onClose={() => setShowLinkModal(false)}
                            title="关联菜单项"
                        >
                            <div className="space-y-4">
                                <div className="text-sm text-gray-600">
                                    为图片 "{linkingImage.alt_text}" 选择要关联的菜单项:
                                </div>
                                {/* 语言与类型选择 */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label className="block text-sm text-gray-700 mb-1">语言（可选）</label>
                                        <Select
                                            value={linkForm.language_id}
                                            onChange={(e)=>setLinkForm('language_id', e.target.value)}
                                            placeholder={null}
                                            options={[
                                                { value: '', label: '通用（所有语言）' },
                                                ...languages.map(lang => ({ value: String(lang.id), label: `${lang.name} (${lang.code})` }))
                                            ]}
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm text-gray-700 mb-1">类型</label>
                                        <Select
                                            value={linkForm.type}
                                            onChange={(e)=>setLinkForm('type', e.target.value)}
                                            placeholder={null}
                                            options={[
                                                { value: 'banner', label: 'banner' },
                                                { value: 'icon', label: 'icon' },
                                            ]}
                                        />
                                    </div>
                                </div>

                                <div className="max-h-60 overflow-y-auto space-y-2">
                                    {menuItems.map(menuItem => (
                                        <div key={menuItem.id} className="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                            <div>
                                                <div className="font-medium text-gray-900">
                                                    {menuItem.key}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    {menuItem.translations?.[0]?.title || '无标题'}
                                                </div>
                                            </div>
                                            <Button
                                                size="sm"
                                                onClick={() => {
                                                    router.post('/telegram/images/attach-to-menu', {
                                                        image_id: linkingImage.id,
                                                        menu_item_id: menuItem.id,
                                                        type: linkForm.type || 'icon',
                                                        language_id: linkForm.language_id || null
                                                    }, {
                                                        onSuccess: () => {
                                                            toast.success('关联成功');
                                                            setShowLinkModal(false);
                                                        },
                                                        onError: () => {
                                                            toast.error('关联失败');
                                                        }
                                                    });
                                                }}
                                            >
                                                关联
                                            </Button>
                                        </div>
                                    ))}
                                </div>

                                <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                                    <Button
                                        variant="outline"
                                        onClick={() => setShowLinkModal(false)}
                                    >
                                        取消
                                    </Button>
                                </div>
                            </div>
                        </Modal>
                    )}
                </div>
            </div>
        </>
    );
}
