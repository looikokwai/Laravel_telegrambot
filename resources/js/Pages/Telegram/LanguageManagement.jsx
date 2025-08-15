import React, { useState, useEffect } from "react";
import { Head, router } from "@inertiajs/react";
import { Button, Card, Input, Select, Modal, Textarea } from "@/Components/UI";
import ImageSelector from "@/Components/ImageSelector";
import { useTranslation } from "react-i18next";
import {
    FaPlus,
    FaEdit,
    FaTrash,
    FaDownload,
    FaUpload,
    FaGlobe,
    FaSearch,
    FaCheck,
    FaTimes,
    FaStar,
    FaRegStar,
    FaLanguage,
    FaFileExport,
    FaFileImport,
    FaSync,
    FaEye,
    FaEyeSlash,
    FaSort,
} from "react-icons/fa";
import { toast } from "react-hot-toast";
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from "@dnd-kit/core";
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
} from "@dnd-kit/sortable";
import { useSortable } from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";

// SortableItem 组件
function SortableItem({
    id,
    language,
    selectedLanguages,
    setSelectedLanguages,
    handleToggleStatus,
    handleSetDefault,
    handleEdit,
    handleDelete,
}) {
    const { t } = useTranslation();
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`border border-gray-200 rounded-lg p-4 bg-white hover:shadow-md transition-shadow ${
                isDragging ? "shadow-lg opacity-50" : ""
            }`}
        >
            <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                    {/* 拖拽手柄 */}
                    <div
                        {...attributes}
                        {...listeners}
                        className="cursor-move text-gray-400 hover:text-gray-600"
                    >
                        <FaSort />
                    </div>

                    {/* 选择框 */}
                    <input
                        type="checkbox"
                        checked={selectedLanguages.has(language.id)}
                        onChange={(e) => {
                            const newSelected = new Set(selectedLanguages);
                            if (e.target.checked) {
                                newSelected.add(language.id);
                            } else {
                                newSelected.delete(language.id);
                            }
                            setSelectedLanguages(newSelected);
                        }}
                        className="rounded border-gray-300 text-blue-600 shadow-sm"
                    />

                    {/* 语言信息 */}
                    <div className="flex items-center space-x-3">
                        <div className="text-2xl">
                            {language.flag_emoji || "🌐"}
                        </div>
                        <div>
                            <div className="flex items-center space-x-2">
                                <h3 className="text-lg font-medium text-gray-900">
                                    {language.name}
                                </h3>
                                <span className="text-sm text-gray-500">
                                    ({language.code.toUpperCase()})
                                </span>
                                {language.is_default && (
                                    <span className="inline-flex items-center px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">
                                        <FaStar className="mr-1" />
                                        {t("telegram.languages.default")}
                                    </span>
                                )}
                                {language.is_rtl && (
                                    <span className="inline-flex items-center px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded-full">
                                        {t("telegram.languages.rtl")}
                                    </span>
                                )}
                            </div>
                            <div className="text-sm text-gray-600">
                                {language.native_name}
                            </div>
                            <div className="text-xs text-gray-500 mt-1">
                                {t("telegram.languages.sortOrderLabel")} {language.sort_order}
                            </div>
                            {/* 语言选择设置信息 */}
                            {(language.selection_title ||
                                language.selection_prompt ||
                                language.selection_image) && (
                                <div className="text-xs text-gray-400 mt-1 space-y-1">
                                    {language.selection_title && (
                                        <div className="flex items-center space-x-1">
                                            <span className="font-medium">
                                                {t("telegram.languages.selectionTitleLabel")}
                                            </span>
                                            <span className="truncate max-w-32">
                                                {language.selection_title}
                                            </span>
                                        </div>
                                    )}
                                    {language.selection_prompt && (
                                        <div className="flex items-center space-x-1">
                                            <span className="font-medium">
                                                {t("telegram.languages.selectionPromptLabel")}
                                            </span>
                                            <span className="truncate max-w-32">
                                                {language.selection_prompt}
                                            </span>
                                        </div>
                                    )}
                                    {language.selection_image && (
                                        <div className="flex items-center space-x-1">
                                            <span className="font-medium">
                                                {t("telegram.languages.selectionImageLabel")}
                                            </span>
                                            <span className="text-blue-500">
                                                {t("telegram.languages.selectionImageSet")}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* 操作按钮 */}
                <div className="flex items-center space-x-2">
                    {/* 状态切换 */}
                    <button
                        onClick={() =>
                            handleToggleStatus(language.id, language.is_active)
                        }
                        className={`p-2 rounded-full ${
                            language.is_active
                                ? "text-green-600 hover:bg-green-50"
                                : "text-gray-400 hover:bg-gray-50"
                        }`}
                        title={
                            language.is_active
                                ? t("telegram.languages.disableLanguage")
                                : t("telegram.languages.enableLanguage")
                        }
                    >
                        {language.is_active ? <FaEye /> : <FaEyeSlash />}
                    </button>

                    {/* 设置默认 */}
                    {!language.is_default && (
                        <button
                            onClick={() => handleSetDefault(language.id)}
                            className="p-2 rounded-full text-yellow-600 hover:bg-yellow-50"
                            title={t("telegram.languages.setAsDefault")}
                        >
                            <FaRegStar />
                        </button>
                    )}

                    {/* 编辑 */}
                    <button
                        onClick={() => handleEdit(language)}
                        className="p-2 rounded-full text-blue-600 hover:bg-blue-50"
                        title={t("telegram.languages.editLanguage")}
                    >
                        <FaEdit />
                    </button>

                    {/* 删除 */}
                    {!language.is_default && (
                        <button
                            onClick={() => handleDelete(language.id)}
                            className="p-2 rounded-full text-red-600 hover:bg-red-50"
                            title={t("telegram.languages.deleteLanguage")}
                        >
                            <FaTrash />
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}

export default function LanguageManagement({
    languages = [],
    menuItems = [],
    stats = {},
    filters = {},
    flash = {},
    availableImages = [],
}) {
    const { t } = useTranslation();
    const [selectedLanguages, setSelectedLanguages] = useState(new Set());
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showTranslationModal, setShowTranslationModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [editingLanguage, setEditingLanguage] = useState(null);
    const [translatingMenuItem, setTranslatingMenuItem] = useState(null);
    const [searchTerm, setSearchTerm] = useState(filters.search || "");
    const [statusFilter, setStatusFilter] = useState(filters.status || "");

    // 调试信息
    console.log("filters:", filters);
    console.log("statusFilter:", statusFilter);
    const [formData, setFormData] = useState({
        code: "",
        name: "",
        native_name: "",
        flag_emoji: "",
        is_rtl: false,
        is_active: true,
        is_default: false,
        selection_title: "",
        selection_prompt: "",
        selection_image_id: "",
        back_label: "",
    });
    const [translations, setTranslations] = useState({});
    const [importData, setImportData] = useState("");
    const [missingTranslations, setMissingTranslations] = useState([]);

    // 重置表单
    const resetForm = () => {
        setFormData({
            code: "",
            name: "",
            native_name: "",
            flag_emoji: "",
            is_rtl: false,
            is_active: true,
            is_default: false,
            selection_title: null,
            selection_prompt: null,
            selection_image_id: null,
            back_label: "",
        });
    };

    // 处理创建语言
    const handleCreate = () => {
        resetForm();
        setShowCreateModal(true);
    };

    // 处理编辑语言
    const handleEdit = (language) => {
        setEditingLanguage(language);
        setFormData({
            code: language.code,
            name: language.name,
            native_name: language.native_name || "",
            flag_emoji: language.flag_emoji || "",
            is_rtl: language.is_rtl,
            is_active: language.is_active,
            is_default: language.is_default,
            selection_title: language.selection_title || "",
            selection_prompt: language.selection_prompt || "",
            selection_image_id: language.selection_image?.id || "",
            back_label: language.back_label || "",
        });
        setShowEditModal(true);
    };

    // 处理表单提交
    const handleSubmit = (e) => {
        e.preventDefault();

        const url = editingLanguage
            ? `/telegram/languages/${editingLanguage.id}`
            : "/telegram/languages";

        const method = editingLanguage ? "put" : "post";

        router[method](url, formData, {
            onSuccess: () => {
                setShowCreateModal(false);
                setShowEditModal(false);
                resetForm();
                setEditingLanguage(null);
                router.reload({ only: ["languages", "stats"] });
            },
            onError: (errors) => {
                Object.values(errors).forEach((error) => {
                    toast.error(error);
                });
            },
        });
    };

    // 处理删除语言
    const handleDelete = (languageId) => {
        if (confirm(t("telegram.languages.deleteConfirmation"))) {
            router.delete(`/telegram/languages/${languageId}`, {
                onSuccess: () => {
                    router.reload({ only: ["languages", "stats"] });
                },
                onError: () => {
                    toast.error(t("telegram.languages.deleteFailed"));
                },
            });
        }
    };

    // 处理设置默认语言
    const handleSetDefault = (languageId) => {
        router.post(
            `/telegram/languages/${languageId}/set-default`,
            {},
            {
                onSuccess: () => {
                    router.reload({ only: ["languages", "stats"] });
                },
                onError: () => {
                    toast.error(t("telegram.languages.setDefaultFailed"));
                },
            }
        );
    };

    // 处理切换语言状态
    const handleToggleStatus = (languageId, currentStatus) => {
        router.post(
            `/telegram/languages/${languageId}/toggle-status`,
            {},
            {
                onSuccess: () => {
                    router.reload({ only: ["languages", "stats"] });
                },
                onError: () => {
                    toast.error(t("telegram.languages.statusUpdateFailed"));
                },
            }
        );
    };

    // 全选/取消全选
    const handleSelectAll = () => {
        if (selectedLanguages.size === languages.length) {
            setSelectedLanguages(new Set());
        } else {
            setSelectedLanguages(new Set(languages.map((lang) => lang.id)));
        }
    };

    // 处理导出语言数据
    const handleExport = () => {
        window.location.href = "/telegram/languages/export";
    };

    // 处理搜索
    const handleSearch = () => {
        router.post(
            "/telegram/languages/filter",
            {
                search: searchTerm,
                status: statusFilter,
            },
            {
                onSuccess: (page) => {
                    // 筛选成功，数据会自动更新
                },
                onError: (errors) => {
                    toast.error(t("telegram.languages.filterFailed"));
                },
                preserveState: true,
                preserveScroll: true,
            }
        );
    };
    // 处理管理翻译
    const handleManageTranslations = (menuItem) => {
        setTranslatingMenuItem(menuItem);
        setShowTranslationModal(true);
    };

    // 处理保存翻译
    const handleSaveTranslations = () => {
        // 这里可以添加保存翻译的逻辑
        setShowTranslationModal(false);
        setTranslatingMenuItem(null);
        setTranslations({});
    };

    // 处理导入
    const handleImport = () => {
        // 这里可以添加导入逻辑
        setShowImportModal(false);
        setImportData("");
    };

    // 处理拖拽结束
    const handleDragEnd = (event) => {
        const { active, over } = event;

        if (active.id !== over.id) {
            const oldIndex = languages.findIndex(
                (lang) => lang.id === active.id
            );
            const newIndex = languages.findIndex((lang) => lang.id === over.id);

            const newLanguages = arrayMove(languages, oldIndex, newIndex);

            // 更新排序
            const updateData = newLanguages.map((lang, index) => ({
                id: lang.id,
                sort_order: index + 1,
            }));

            router.post(
                "/telegram/languages/reorder",
                {
                    languages: updateData,
                },
                {
                    onSuccess: () => {
                        router.reload({ only: ["languages"] });
                    },
                    onError: () => {
                        toast.error(t("telegram.languages.sortUpdateFailed"));
                    },
                }
            );
        }
    };

    // 拖拽传感器
    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    return (
        <>
            <Head title={t("telegram.languages.title")} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* 页面标题 */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">
                                    {t("telegram.languages.title")}
                                </h1>
                                <p className="mt-1 text-sm text-gray-500">
                                    {t("telegram.languages.subtitle")}
                                </p>
                            </div>
                            <div className="flex items-center space-x-3">
                                <Button
                                    onClick={handleCreate}
                                    className="flex items-center space-x-2"
                                >
                                    <FaPlus />
                                    <span>
                                        {t("telegram.languages.addLanguage")}
                                    </span>
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* 统计信息 */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <Card padding="default">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-blue-600">
                                    {stats.total_languages || 0}
                                </div>
                                <div className="text-sm text-gray-500">
                                    {t("telegram.languages.totalLanguages")}
                                </div>
                            </div>
                        </Card>
                        <Card padding="default">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-green-600">
                                    {stats.active_languages || 0}
                                </div>
                                <div className="text-sm text-gray-500">
                                    {t("telegram.languages.activeLanguages")}
                                </div>
                            </div>
                        </Card>
                        <Card padding="default">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-purple-600">
                                    {stats.total_translations || 0}
                                </div>
                                <div className="text-sm text-gray-500">
                                    {t("telegram.languages.totalTranslations")}
                                </div>
                            </div>
                        </Card>
                    </div>

                    {/* 搜索和筛选 */}
                    <Card
                        title={t("telegram.languages.searchAndFilter")}
                        padding="default"
                        className="mb-6"
                    >
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <Input
                                    type="text"
                                    placeholder={t(
                                        "telegram.languages.searchLanguages"
                                    )}
                                    value={searchTerm}
                                    onChange={(e) =>
                                        setSearchTerm(e.target.value)
                                    }
                                    onKeyPress={(e) =>
                                        e.key === "Enter" && handleSearch()
                                    }
                                />
                            </div>
                            <div>
                                <Select
                                    value={statusFilter}
                                    onChange={(e) =>
                                        setStatusFilter(e.target.value)
                                    }
                                    placeholder={t(
                                        "telegram.languages.filterByStatus"
                                    )}
                                    options={[
                                        {
                                            value: "",
                                            label: t(
                                                "telegram.languages.allStatuses"
                                            ),
                                        },
                                        {
                                            value: "active",
                                            label: t(
                                                "telegram.languages.active"
                                            ),
                                        },
                                        {
                                            value: "inactive",
                                            label: t(
                                                "telegram.languages.inactive"
                                            ),
                                        },
                                        {
                                            value: "default",
                                            label: t(
                                                "telegram.languages.defaultLanguage"
                                            ),
                                        },
                                    ]}
                                />
                            </div>
                            <div>
                                <Button
                                    onClick={handleSearch}
                                    className="w-full flex items-center justify-center space-x-2"
                                >
                                    <FaSearch />
                                    <span>{t("common.search")}</span>
                                </Button>
                            </div>
                        </div>
                    </Card>

                    {/* 语言列表 */}
                    <Card
                        title={t("telegram.languages.languageList")}
                        padding="none"
                    >
                        <div className="p-6">
                            {languages.length > 0 ? (
                                <>
                                    {/* 批量操作栏 */}
                                    <div className="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                                        <label className="flex items-center">
                                            <input
                                                type="checkbox"
                                                checked={
                                                    selectedLanguages.size ===
                                                        languages.length &&
                                                    languages.length > 0
                                                }
                                                onChange={handleSelectAll}
                                                className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                            />
                                            <span className="ml-2 text-sm text-gray-700">
                                                {t(
                                                    "telegram.languages.selectAll",
                                                    {
                                                        count: selectedLanguages.size,
                                                        total: languages.length,
                                                    }
                                                )}
                                            </span>
                                        </label>

                                        <div className="flex items-center space-x-2">
                                            <span className="text-sm text-gray-500">
                                                {t(
                                                    "telegram.languages.dragToReorder"
                                                )}
                                            </span>
                                            <FaSort className="text-gray-400" />
                                        </div>
                                    </div>

                                    {/* 拖拽排序列表 */}
                                    <DndContext
                                        sensors={sensors}
                                        collisionDetection={closestCenter}
                                        onDragEnd={handleDragEnd}
                                    >
                                        <SortableContext
                                            items={languages.map(
                                                (lang) => lang.id
                                            )}
                                            strategy={
                                                verticalListSortingStrategy
                                            }
                                        >
                                            <div className="space-y-3">
                                                {languages.map((language) => (
                                                    <SortableItem
                                                        key={language.id}
                                                        id={language.id}
                                                        language={language}
                                                        selectedLanguages={
                                                            selectedLanguages
                                                        }
                                                        setSelectedLanguages={
                                                            setSelectedLanguages
                                                        }
                                                        handleToggleStatus={
                                                            handleToggleStatus
                                                        }
                                                        handleSetDefault={
                                                            handleSetDefault
                                                        }
                                                        handleEdit={handleEdit}
                                                        handleDelete={
                                                            handleDelete
                                                        }
                                                    />
                                                ))}
                                            </div>
                                        </SortableContext>
                                    </DndContext>
                                </>
                            ) : (
                                <div className="text-center py-12">
                                    <FaGlobe className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">
                                        {t("telegram.languages.noLanguages")}
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        {t("telegram.languages.startCreating")}
                                    </p>
                                    <div className="mt-6">
                                        <Button
                                            onClick={handleCreate}
                                            className="flex items-center space-x-2"
                                        >
                                            <FaPlus />
                                            <span>
                                                {t(
                                                    "telegram.languages.addLanguage"
                                                )}
                                            </span>
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </Card>

                    {/* 菜单项翻译管理 */}
                    {menuItems.length > 0 && (
                        <Card
                            title={t(
                                "telegram.languages.menuItemTranslationManagement"
                            )}
                            padding="default"
                            className="mt-6"
                        >
                            <div className="space-y-3">
                                {menuItems.map((menuItem) => (
                                    <div
                                        key={menuItem.id}
                                        className="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50"
                                    >
                                        <div>
                                            <div className="font-medium text-gray-900">
                                                {menuItem.key}
                                            </div>
                                            <div className="text-sm text-gray-500">
                                                {t(
                                                    "telegram.languages.translationsCount",
                                                    {
                                                        count:
                                                            menuItem
                                                                .translations
                                                                ?.length || 0,
                                                        total: languages.length,
                                                    }
                                                )}
                                            </div>
                                        </div>
                                        <Button
                                            size="sm"
                                            onClick={() =>
                                                handleManageTranslations(
                                                    menuItem
                                                )
                                            }
                                            className="flex items-center space-x-2"
                                        >
                                            <FaLanguage />
                                            <span>
                                                {t(
                                                    "telegram.languages.manageTranslations"
                                                )}
                                            </span>
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        </Card>
                    )}

                    {/* 创建/编辑语言模态框 */}
                    {(showCreateModal || showEditModal) && (
                        <Modal
                            isOpen={showCreateModal || showEditModal}
                            onClose={() => {
                                setShowCreateModal(false);
                                setShowEditModal(false);
                                resetForm();
                                setEditingLanguage(null);
                            }}
                            title={
                                editingLanguage
                                    ? t("telegram.languages.editLanguage")
                                    : t("telegram.languages.addLanguage")
                            }
                            size="xl"
                            bodyClassName="max-h-[70vh]"
                        >
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            {t(
                                                "telegram.languages.languageCode"
                                            )}{" "}
                                            *
                                        </label>
                                        <Input
                                            type="text"
                                            value={formData.code}
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    code: e.target.value,
                                                })
                                            }
                                            placeholder={t(
                                                "telegram.languages.exampleCode",
                                                {
                                                    example: "en, zh, fr",
                                                }
                                            )}
                                            required
                                            disabled={!!editingLanguage}
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            {t(
                                                "telegram.languages.languageName"
                                            )}{" "}
                                            *
                                        </label>
                                        <Input
                                            type="text"
                                            value={formData.name}
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    name: e.target.value,
                                                })
                                            }
                                            placeholder={t(
                                                "telegram.languages.exampleName",
                                                {
                                                    example:
                                                        "English, 中文, Français",
                                                }
                                            )}
                                            required
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            {t("telegram.languages.nativeName")}{" "}
                                            *
                                        </label>
                                        <Input
                                            type="text"
                                            value={formData.native_name}
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    native_name: e.target.value,
                                                })
                                            }
                                            placeholder={t(
                                                "telegram.languages.exampleNativeName",
                                                {
                                                    example:
                                                        "English, 中文, Français",
                                                }
                                            )}
                                            required
                                        />
                                    </div>
                                    {/* <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            {t('flag_emoji')}
                                        </label>
                                        <Input
                                            type="text"
                                            value={formData.flag_emoji}
                                            onChange={(e) => setFormData({...formData, flag_emoji: e.target.value})}
                                            placeholder={t('example_flag_emoji', { example: '🇺🇸, 🇨🇳, 🇫🇷' })}
                                        />
                                    </div> */}
                                </div>

                                {/* 语言选择提示设置 */}
                                <div className="border-t border-gray-200 pt-4">
                                    <h4 className="text-sm font-medium text-gray-900 mb-3">
                                        {t(
                                            "telegram.languages.languageSelectionSettings"
                                        )}
                                    </h4>

                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                {t(
                                                    "telegram.languages.selectionTitle"
                                                )}
                                            </label>
                                            <Input
                                                type="text"
                                                value={formData.selection_title}
                                                onChange={(e) =>
                                                    setFormData({
                                                        ...formData,
                                                        selection_title:
                                                            e.target.value,
                                                    })
                                                }
                                                placeholder={t(
                                                    "telegram.languages.exampleSelectionTitle",
                                                    {
                                                        example:
                                                            "请选择语言 / Please select language",
                                                    }
                                                )}
                                            />
                                            <p className="text-xs text-gray-500 mt-1">
                                                {t("telegram.languages.emptyToUseDefaultText")}
                                            </p>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                {t("telegram.languages.selectionPrompt")}
                                            </label>
                                            <Textarea
                                                value={
                                                    formData.selection_prompt
                                                }
                                                onChange={(e) =>
                                                    setFormData({
                                                        ...formData,
                                                        selection_prompt:
                                                            e.target.value,
                                                    })
                                                }
                                                placeholder={t(
                                                    "telegram.languages.exampleSelectionPrompt",
                                                    {
                                                        example:
                                                            "选择您的首选语言以获得更好的体验",
                                                    }
                                                )}
                                                rows={2}
                                            />
                                            <p className="text-xs text-gray-500 mt-1">
                                                {t("telegram.languages.emptyToUseDefaultText")}
                                            </p>
                                        </div>

                                        <ImageSelector
                                            value={formData.selection_image_id}
                                            onChange={(value) =>
                                                setFormData({
                                                    ...formData,
                                                    selection_image_id: value,
                                                })
                                            }
                                            label={t("telegram.languages.selectImage")}
                                            placeholder={t(
                                                "telegram.languages.chooseImageOptional"
                                            )}
                                            showUpload={true}
                                            availableImages={availableImages}
                                        />
                                    </div>
                                </div>

                                {/* 返回按钮文案 */}
                                <div className="border-t border-gray-200 pt-4">
                                    <h4 className="text-sm font-medium text-gray-900 mb-3">
                                        {t("telegram.languages.backButtonText")}
                                    </h4>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            {t("telegram.languages.backButtonTextOptional")}
                                        </label>
                                        <Input
                                            type="text"
                                            value={formData.back_label}
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    back_label: e.target.value,
                                                })
                                            }
                                            placeholder={t(
                                                "telegram.languages.exampleBackLabel",
                                                {
                                                    example:
                                                        "🔙 返回 / Back / Kembali",
                                                }
                                            )}
                                        />
                                                                                    <p className="text-xs text-gray-500 mt-1">
                                                {t("telegram.languages.emptyToUseDefaultText")}
                                            </p>
                                    </div>
                                </div>

                                <div className="space-y-3">
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={formData.is_rtl}
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    is_rtl: e.target.checked,
                                                })
                                            }
                                            className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">
                                            {t("telegram.languages.rtlLanguage")}
                                        </span>
                                    </label>

                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={formData.is_active}
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    is_active: e.target.checked,
                                                })
                                            }
                                            className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">
                                            {t("telegram.languages.enableLanguage")}
                                        </span>
                                    </label>

                                    {!editingLanguage?.is_default && (
                                        <label className="flex items-center">
                                            <input
                                                type="checkbox"
                                                checked={formData.is_default}
                                                onChange={(e) =>
                                                    setFormData({
                                                        ...formData,
                                                        is_default:
                                                            e.target.checked,
                                                    })
                                                }
                                                className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                            />
                                            <span className="ml-2 text-sm text-gray-700">
                                                {t("telegram.languages.setAsDefaultLanguage")}
                                            </span>
                                        </label>
                                    )}
                                </div>

                                <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setShowCreateModal(false);
                                            setShowEditModal(false);
                                            resetForm();
                                            setEditingLanguage(null);
                                        }}
                                    >
                                        {t("common.cancel")}
                                    </Button>
                                    <Button type="submit">
                                        {editingLanguage
                                            ? t("telegram.languages.update")
                                            : t("telegram.languages.create")}
                                    </Button>
                                </div>
                            </form>
                        </Modal>
                    )}

                    {/* 翻译管理模态框 */}
                    {showTranslationModal && translatingMenuItem && (
                        <Modal
                            isOpen={showTranslationModal}
                            onClose={() => {
                                setShowTranslationModal(false);
                                setTranslatingMenuItem(null);
                                setTranslations({});
                            }}
                            title={`${t("telegram.languages.manageTranslations")}: ${
                                translatingMenuItem.key
                            }`}
                            size="large"
                        >
                            <div className="space-y-6">
                                <div className="text-sm text-gray-600">
                                    {t("telegram.languages.manageTranslationsForMenuItem", {
                                        menuItemKey: translatingMenuItem.key,
                                    })}
                                </div>

                                <div className="space-y-4 max-h-96 overflow-y-auto">
                                    {languages.map((language) => (
                                        <div
                                            key={language.code}
                                            className="border border-gray-200 rounded-lg p-4"
                                        >
                                            <div className="flex items-center space-x-2 mb-3">
                                                <span className="text-lg">
                                                    {language.flag_emoji ||
                                                        "🌐"}
                                                </span>
                                                <span className="font-medium text-gray-900">
                                                    {language.name}
                                                </span>
                                                <span className="text-sm text-gray-500">
                                                    (
                                                    {language.code.toUpperCase()}
                                                    )
                                                </span>
                                                {language.is_default && (
                                                    <span className="inline-flex items-center px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">
                                                        {t("telegram.languages.default")}
                                                    </span>
                                                )}
                                            </div>

                                            <div className="space-y-3">
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                                        {t("telegram.languages.title")}
                                                    </label>
                                                    <Input
                                                        type="text"
                                                        value={
                                                            translations[
                                                                language.code
                                                            ]?.title || ""
                                                        }
                                                        onChange={(e) =>
                                                            setTranslations({
                                                                ...translations,
                                                                [language.code]:
                                                                    {
                                                                        ...translations[
                                                                            language
                                                                                .code
                                                                        ],
                                                                        title: e
                                                                            .target
                                                                            .value,
                                                                    },
                                                            })
                                                        }
                                                        placeholder={t(
                                                            "telegram.languages.enterTitleTranslation"
                                                        )}
                                                    />
                                                </div>

                                                <div>
                                                                                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                            {t("telegram.languages.description")}
                                                        </label>
                                                    <Textarea
                                                        value={
                                                            translations[
                                                                language.code
                                                            ]?.description || ""
                                                        }
                                                        onChange={(e) =>
                                                            setTranslations({
                                                                ...translations,
                                                                [language.code]:
                                                                    {
                                                                        ...translations[
                                                                            language
                                                                                .code
                                                                        ],
                                                                        description:
                                                                            e
                                                                                .target
                                                                                .value,
                                                                    },
                                                            })
                                                        }
                                                        placeholder={t(
                                                            "telegram.languages.enterDescriptionTranslation"
                                                        )}
                                                        rows={2}
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                                    <Button
                                        variant="outline"
                                        onClick={() => {
                                            setShowTranslationModal(false);
                                            setTranslatingMenuItem(null);
                                            setTranslations({});
                                        }}
                                    >
                                        {t("common.cancel")}
                                    </Button>
                                    <Button
                                        onClick={handleSaveTranslations}
                                        className="flex items-center space-x-2"
                                    >
                                        <FaCheck />
                                        <span>{t("telegram.languages.saveTranslations")}</span>
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
