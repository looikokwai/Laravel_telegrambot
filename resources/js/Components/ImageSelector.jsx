import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Button, Modal, Input } from '@/Components/UI';
import { FaPlus, FaUpload, FaTrash, FaCheck, FaTimes, FaImage, FaSearch } from 'react-icons/fa';
import { toast } from 'react-hot-toast';

const ImageSelector = ({
    value,
    onChange,
    label = "选择图片",
    placeholder = "请选择图片（可选）",
    className = "",
    showUpload = true
}) => {
    const [availableImages, setAvailableImages] = useState([]);
    const [showImageModal, setShowImageModal] = useState(false);
    const [showUploadModal, setShowUploadModal] = useState(false);
    const [selectedImage, setSelectedImage] = useState(null);
    const [uploadFile, setUploadFile] = useState(null);
    const [uploadPreview, setUploadPreview] = useState(null);
    const [loading, setLoading] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');

    // 获取可用图片列表
    const fetchAvailableImages = async () => {
        try {
            // 获取 CSRF token
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // 使用fetch API获取JSON数据
            const response = await fetch('/telegram/images', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token || ''
                }
            });
            const data = await response.json();
            if (data.success) {
                setAvailableImages(data.data || []);
            }
        } catch (error) {
            console.error('获取图片列表失败:', error);
        }
    };

    // 组件加载时获取图片列表
    useEffect(() => {
        fetchAvailableImages();
    }, []);

    // 根据value找到当前选中的图片
    useEffect(() => {
        if (value && availableImages.length > 0) {
            const image = availableImages.find(img => img.id === parseInt(value));
            setSelectedImage(image || null);
        } else {
            setSelectedImage(null);
        }
    }, [value, availableImages]);

    // 处理文件选择
    const handleFileSelect = (e) => {
        const file = e.target.files[0];
        if (file) {
            setUploadFile(file);
            // 创建预览
            const reader = new FileReader();
            reader.onload = (e) => {
                setUploadPreview(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    };

    // 处理图片上传
    const handleUpload = async () => {
        if (!uploadFile) return;

        setLoading(true);
        const formData = new FormData();
        formData.append('image', uploadFile);
        formData.append('alt_text', uploadFile.name);

        try {
            // 使用 Inertia.js router.post 方法，它会自动处理 CSRF token
            router.post('/telegram/images/upload', formData, {
                onSuccess: (page) => {
                    toast.success('图片上传成功');
                    fetchAvailableImages();
                    setShowUploadModal(false);
                    setUploadFile(null);
                    setUploadPreview(null);

                    // 如果返回了新上传的图片数据，自动选择它
                    if (page.props.flash && page.props.flash.uploadedImage) {
                        onChange(page.props.flash.uploadedImage.id.toString());
                    }
                },
                onError: (errors) => {
                    console.error('上传失败:', errors);
                    toast.error('上传失败');
                },
                preserveState: true,
                preserveScroll: true
            });
        } catch (error) {
            console.error('上传失败:', error);
            toast.error('上传失败');
        } finally {
            setLoading(false);
        }
    };

    // 过滤图片
    const filteredImages = availableImages.filter(image =>
        image.filename.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (image.alt_text && image.alt_text.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    return (
        <div className={className}>
            <label className="block text-sm font-medium text-gray-700 mb-1">
                {label}
            </label>

            {/* 当前选中的图片预览 */}
            {selectedImage ? (
                <div className="mb-3 p-3 border border-gray-200 rounded-lg bg-gray-50">
                    <div className="flex items-center space-x-3">
                        <div className="flex-shrink-0">
                            <img
                                src={selectedImage.url}
                                alt={selectedImage.alt_text || selectedImage.filename}
                                className="w-16 h-16 object-cover rounded border"
                            />
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-gray-900 truncate">
                                {selectedImage.filename}
                            </p>
                            <p className="text-xs text-gray-500">
                                {selectedImage.width}×{selectedImage.height} • {(selectedImage.file_size / 1024).toFixed(1)}KB
                            </p>
                            {selectedImage.alt_text && (
                                <p className="text-xs text-gray-600 truncate">
                                    {selectedImage.alt_text}
                                </p>
                            )}
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => onChange('')}
                            className="text-red-600 hover:text-red-700"
                        >
                            <FaTimes className="w-3 h-3" />
                        </Button>
                    </div>
                </div>
            ) : (
                <div className="mb-3 p-6 border-2 border-dashed border-gray-300 rounded-lg text-center">
                    <FaImage className="mx-auto h-8 w-8 text-gray-400 mb-2" />
                    <p className="text-sm text-gray-500">{placeholder}</p>
                </div>
            )}

            {/* 操作按钮 */}
            <div className="flex space-x-2">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => setShowImageModal(true)}
                    className="flex items-center space-x-2"
                >
                    <FaImage className="w-4 h-4" />
                    <span>选择图片</span>
                </Button>

                {showUpload && (
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => setShowUploadModal(true)}
                        className="flex items-center space-x-2"
                    >
                        <FaUpload className="w-4 h-4" />
                        <span>上传新图片</span>
                    </Button>
                )}
            </div>

            <p className="text-xs text-gray-500 mt-1">
                留空将只发送文本消息，选择图片时会一起发送
            </p>

            {/* 图片选择模态框 */}
            <Modal
                isOpen={showImageModal}
                onClose={() => setShowImageModal(false)}
                title="选择图片"
                size="large"
            >
                <div className="space-y-4">
                    {/* 搜索框 */}
                    <div className="flex space-x-2">
                        <Input
                            type="text"
                            placeholder="搜索图片..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="flex-1"
                        />
                        {showUpload && (
                            <Button
                                onClick={() => {
                                    setShowImageModal(false);
                                    setShowUploadModal(true);
                                }}
                                className="flex items-center space-x-2"
                            >
                                <FaPlus className="w-4 h-4" />
                                <span>上传新图片</span>
                            </Button>
                        )}
                    </div>

                    {/* 图片网格 */}
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 max-h-96 overflow-y-auto">
                        {filteredImages.map(image => (
                            <div
                                key={image.id}
                                className={`relative border-2 rounded-lg p-2 cursor-pointer transition-all ${
                                    value === image.id.toString()
                                        ? 'border-blue-500 bg-blue-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                }`}
                                onClick={() => {
                                    onChange(image.id.toString());
                                    setShowImageModal(false);
                                }}
                            >
                                <img
                                    src={image.url}
                                    alt={image.alt_text || image.filename}
                                    className="w-full h-24 object-cover rounded mb-2"
                                />
                                <p className="text-xs font-medium text-gray-900 truncate">
                                    {image.filename}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {image.width}×{image.height}
                                </p>
                                {value === image.id.toString() && (
                                    <div className="absolute top-1 right-1 bg-blue-500 text-white rounded-full p-1">
                                        <FaCheck className="w-3 h-3" />
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>

                    {filteredImages.length === 0 && (
                        <div className="text-center py-8">
                            <FaImage className="mx-auto h-12 w-12 text-gray-400 mb-4" />
                            <p className="text-gray-500">没有找到图片</p>
                            {searchTerm && (
                                <p className="text-sm text-gray-400 mt-1">
                                    尝试调整搜索条件
                                </p>
                            )}
                        </div>
                    )}

                    <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <Button
                            variant="outline"
                            onClick={() => setShowImageModal(false)}
                        >
                            取消
                        </Button>
                        <Button
                            onClick={() => {
                                onChange('');
                                setShowImageModal(false);
                            }}
                            variant="outline"
                        >
                            清除选择
                        </Button>
                    </div>
                </div>
            </Modal>

            {/* 图片上传模态框 */}
            <Modal
                isOpen={showUploadModal}
                onClose={() => {
                    setShowUploadModal(false);
                    setUploadFile(null);
                    setUploadPreview(null);
                }}
                title="上传新图片"
                size="medium"
            >
                <div className="space-y-4">
                    {/* 文件选择 */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            选择图片文件
                        </label>
                        <input
                            type="file"
                            accept="image/*"
                            onChange={handleFileSelect}
                            className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                        />
                    </div>

                    {/* 预览 */}
                    {uploadPreview && (
                        <div className="border border-gray-200 rounded-lg p-4">
                            <p className="text-sm font-medium text-gray-700 mb-2">预览</p>
                            <img
                                src={uploadPreview}
                                alt="预览"
                                className="max-w-full h-48 object-contain mx-auto rounded"
                            />
                            <p className="text-xs text-gray-500 mt-2 text-center">
                                文件名: {uploadFile?.name}
                            </p>
                        </div>
                    )}

                    <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <Button
                            variant="outline"
                            onClick={() => {
                                setShowUploadModal(false);
                                setUploadFile(null);
                                setUploadPreview(null);
                            }}
                        >
                            取消
                        </Button>
                        <Button
                            onClick={handleUpload}
                            disabled={!uploadFile || loading}
                            className="flex items-center space-x-2"
                        >
                            <FaUpload className="w-4 h-4" />
                            <span>{loading ? '上传中...' : '上传'}</span>
                        </Button>
                    </div>
                </div>
            </Modal>
        </div>
    );
};

export default ImageSelector;
