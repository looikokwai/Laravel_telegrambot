import React, { useState, useEffect } from "react";
import { Head, useForm, router } from "@inertiajs/react";
import { Button, Card, Select, Textarea, Input } from "@/Components/UI";
import { useTranslation } from 'react-i18next';
import {
    FaPaperPlane,
    FaUsers,
    FaChartLine,
    FaGlobe,
    FaExclamationTriangle,
    FaInfoCircle,
    FaImage,
    FaKeyboard,
    FaPlus,
    FaTrash,
    FaHistory,
    FaSpinner,
} from "react-icons/fa";

export default function GroupBroadcast({ groups = [], stats = null, broadcasts = null }) {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState("broadcast");
    const [broadcastHistory, setBroadcastHistory] = useState(broadcasts);
    const [broadcastStats, setBroadcastStats] = useState(stats);

    const { data, setData, post, processing, errors, reset } = useForm({
        message: "",
        target_groups: [],
        target_type: "selected",
        image: null,
        keyboard: [],
    });

    const handleBroadcast = (e) => {
        e.preventDefault();
        post("/telegram/group-broadcast", {
            onSuccess: () => {
                reset("message");
            },
        });
    };

    const loadBroadcastHistory = () => {
        // 通过 Inertia 获取广播历史数据
        router.get('/telegram/group-broadcast', {
            tab: 'history'
        }, {
            preserveState: true,
            onSuccess: (page) => {
                if (page.props.broadcasts) {
                    setBroadcastHistory(page.props.broadcasts);
                }
                if (page.props.broadcastStats) {
                    setBroadcastStats(page.props.broadcastStats);
                }
            }
        });
    };

    const getStatusBadge = (status) => {
        const statusConfig = {
            pending: { color: 'bg-yellow-100 text-yellow-800', text: t('telegram.groupBroadcast.pending') },
            completed: { color: 'bg-green-100 text-green-800', text: t('telegram.groupBroadcast.completed') },
            completed_with_errors: { color: 'bg-orange-100 text-orange-800', text: t('telegram.groupBroadcast.completedWithErrors') },
            failed: { color: 'bg-red-100 text-red-800', text: t('telegram.groupBroadcast.failed') }
        };

        const config = statusConfig[status] || statusConfig.pending;
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.color}`}>
                {config.text}
            </span>
        );
    };

    const formatDate = (dateString) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleString();
    };

    // 安全地处理群组数据
    const allGroups = groups || [];

    return (
        <>
            <Head title={t('telegram.groupBroadcast.groupBroadcast')} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">
                            {t('telegram.groupBroadcast.groupBroadcast')}
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            {t('telegram.groupBroadcast.groupBroadcastSubtitle')}
                        </p>
                    </div>

                    {/* 标签导航 */}
                    <div className="mb-6">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8">
                                <button
                                    onClick={() => setActiveTab("broadcast")}
                                    className={`py-2 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === "broadcast"
                                            ? "border-blue-500 text-blue-600"
                                            : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                                    }`}
                                >
                                    {t('telegram.groupBroadcast.broadcastMessage')}
                                </button>
                                <button
                                    onClick={() => {
                                        setActiveTab("history");
                                        loadBroadcastHistory();
                                    }}
                                    className={`py-2 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === "history"
                                            ? "border-blue-500 text-blue-600"
                                            : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                                    }`}
                                >
                                    {t('telegram.groupBroadcast.broadcastHistory')}
                                </button>
                                <button
                                    onClick={() => setActiveTab("stats")}
                                    className={`py-2 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === "stats"
                                            ? "border-blue-500 text-blue-600"
                                            : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                                    }`}
                                >
                                    {t('telegram.groupBroadcast.stats')}
                                </button>
                            </nav>
                        </div>
                    </div>

                    {/* 广播消息标签页 */}
                    {activeTab === "broadcast" && (
                        <Card>
                            <form onSubmit={handleBroadcast} className="space-y-6">
                                {/* 目标群组选择 */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {t('telegram.groupBroadcast.targetGroups')}
                                    </label>
                                    <Select
                                        value={data.target_type}
                                        onChange={(e) => setData('target_type', e.target.value)}
                                        className="w-full"
                                        options={[
                                            { value: 'selected', label: t('telegram.groupBroadcast.selectedGroups') },
                                            { value: 'all', label: t('telegram.groupBroadcast.allGroups') },
                                            { value: 'active', label: t('telegram.groupBroadcast.activeGroups') }
                                        ]}
                                    />
                                </div>

                                {/* 群组选择（当选择"selected"时显示） */}
                                {data.target_type === "selected" && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            {t('telegram.groupBroadcast.selectGroups')}
                                        </label>
                                        <div className="max-h-60 overflow-y-auto border border-gray-300 rounded-md p-4">
                                            {allGroups.length === 0 ? (
                                                <p className="text-gray-500 text-sm">{t('telegram.groups.noGroupsFound')}</p>
                                            ) : (
                                                <div className="space-y-2">
                                                    {allGroups.map((group) => (
                                                        <label key={group.id} className="flex items-center">
                                                            <input
                                                                type="checkbox"
                                                                value={group.id}
                                                                checked={data.target_groups.includes(group.id)}
                                                                onChange={(e) => {
                                                                    const newGroups = e.target.checked
                                                                        ? [...data.target_groups, group.id]
                                                                        : data.target_groups.filter(id => id !== group.id);
                                                                    setData('target_groups', newGroups);
                                                                }}
                                                                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                            />
                                                            <span className="ml-2 text-sm text-gray-900">
                                                                {group.title} ({group.group_id})
                                                            </span>
                                                        </label>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                        {errors.target_groups && (
                                            <p className="mt-1 text-sm text-red-600">{errors.target_groups}</p>
                                        )}
                                    </div>
                                )}

                                {/* 消息内容 */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {t('telegram.groupBroadcast.messageContent')}
                                    </label>
                                    <Textarea
                                        value={data.message}
                                        onChange={(e) => setData('message', e.target.value)}
                                        placeholder={t('telegram.groupBroadcast.enterMessage')}
                                        rows={6}
                                        required
                                    />
                                    {errors.message && (
                                        <p className="mt-1 text-sm text-red-600">{errors.message}</p>
                                    )}
                                </div>

                                {/* 图片上传 */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {t('telegram.groupBroadcast.imageUpload')} ({t('common.optional')})
                                    </label>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        onChange={(e) => setData('image', e.target.files[0])}
                                        className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    />
                                    {errors.image && (
                                        <p className="mt-1 text-sm text-red-600">{errors.image}</p>
                                    )}
                                </div>

                                {/* 队列警告 */}
                                <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                    <div className="flex">
                                        <div className="flex-shrink-0">
                                            <FaExclamationTriangle className="h-5 w-5 text-yellow-400" />
                                        </div>
                                        <div className="ml-3">
                                            <h3 className="text-sm font-medium text-yellow-800">
                                                {t('telegram.groupBroadcast.queueWarning')}
                                            </h3>
                                            <div className="mt-2 text-sm text-yellow-700">
                                                <p>{t('telegram.groupBroadcast.queueWarningDetail')}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* 发送按钮 */}
                                <div className="flex justify-end">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="flex items-center space-x-2"
                                    >
                                        {processing ? (
                                            <FaSpinner className="w-4 h-4 animate-spin" />
                                        ) : (
                                            <FaPaperPlane className="w-4 h-4" />
                                        )}
                                        <span>{t('telegram.groupBroadcast.sendMessage')}</span>
                                    </Button>
                                </div>
                            </form>
                        </Card>
                    )}

                    {/* 广播历史标签页 */}
                    {activeTab === "history" && (
                        <Card>
                            <div className="space-y-4">
                                {broadcastHistory ? (
                                    broadcastHistory.data && broadcastHistory.data.length > 0 ? (
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200">
                                                <thead className="bg-gray-50">
                                                    <tr>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            {t('telegram.groupBroadcast.messageContent')}
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            {t('telegram.groupBroadcast.targetGroups')}
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            {t('telegram.groupBroadcast.sendStats')}
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            {t('telegram.groupBroadcast.status')}
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            {t('telegram.groupBroadcast.sendTime')}
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className="bg-white divide-y divide-gray-200">
                                                    {broadcastHistory.data.map((broadcast) => (
                                                        <tr key={broadcast.id}>
                                                            <td className="px-6 py-4">
                                                                <div className="text-sm text-gray-900">
                                                                    {broadcast.message.length > 50
                                                                        ? broadcast.message.substring(0, 50) + '...'
                                                                        : broadcast.message
                                                                    }
                                                                </div>
                                                                <div className="flex items-center space-x-2 mt-1">
                                                                    {broadcast.image_path && (
                                                                        <span className="inline-flex items-center text-xs text-gray-500">
                                                                            <FaImage className="w-3 h-3 mr-1" />
                                                                            {t('telegram.groupBroadcast.image')}
                                                                        </span>
                                                                    )}
                                                                    {broadcast.keyboard && (
                                                                        <span className="inline-flex items-center text-xs text-gray-500">
                                                                            <FaKeyboard className="w-3 h-3 mr-1" />
                                                                            {t('telegram.groupBroadcast.keyboard')}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </td>
                                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                                {broadcast.target_type === 'all' && t('telegram.groupBroadcast.allGroups')}
                                                                {broadcast.target_type === 'selected' && t('telegram.groupBroadcast.selectedGroups')}
                                                                {broadcast.target_type === 'active' && t('telegram.groupBroadcast.activeGroups')}
                                                            </td>
                                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                                <div>{t('telegram.groupBroadcast.total')}: <span className="font-medium">{broadcast.total_groups}</span></div>
                                                                <div>{t('telegram.groupBroadcast.sent')}: <span className="font-medium text-green-600">{broadcast.sent_count || 0}</span></div>
                                                                <div>{t('telegram.groupBroadcast.failed')}: <span className="font-medium text-red-600">{broadcast.failed_count || 0}</span></div>
                                                            </td>
                                                            <td className="px-6 py-4">
                                                                <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                                                    broadcast.status === 'completed'
                                                                        ? 'bg-green-100 text-green-800'
                                                                        : broadcast.status === 'completed_with_errors'
                                                                        ? 'bg-yellow-100 text-yellow-800'
                                                                        : broadcast.status === 'pending'
                                                                        ? 'bg-blue-100 text-blue-800'
                                                                        : 'bg-red-100 text-red-800'
                                                                }`}>
                                                                    {broadcast.status === 'completed' ? t('telegram.groupBroadcast.completed') :
                                                                     broadcast.status === 'completed_with_errors' ? t('telegram.groupBroadcast.completedWithErrors') :
                                                                     broadcast.status === 'pending' ? t('telegram.groupBroadcast.pending') : t('telegram.groupBroadcast.failed')}
                                                                </span>
                                                            </td>
                                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                                {formatDate(broadcast.created_at)}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    ) : (
                                        <p className="text-center text-gray-500 py-8">
                                            {t('telegram.groupBroadcast.noBroadcastRecords')}
                                        </p>
                                    )
                                ) : (
                                    <div className="text-center py-8">
                                        <FaSpinner className="w-8 h-8 text-gray-400 animate-spin mx-auto mb-4" />
                                        <p className="text-gray-500">{t('common.loading')}</p>
                                    </div>
                                )}
                            </div>
                        </Card>
                    )}

                    {/* 统计标签页 */}
                    {activeTab === "stats" && (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <Card>
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <FaUsers className="h-8 w-8 text-blue-600" />
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-500">
                                            {t('telegram.groupBroadcast.totalGroups')}
                                        </p>
                                        <p className="text-2xl font-semibold text-gray-900">
                                            {broadcastStats?.total_groups || 0}
                                        </p>
                                    </div>
                                </div>
                            </Card>

                            <Card>
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <FaPaperPlane className="h-8 w-8 text-green-600" />
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-500">
                                            {t('telegram.groupBroadcast.totalBroadcasts')}
                                        </p>
                                        <p className="text-2xl font-semibold text-gray-900">
                                            {broadcastStats?.total_broadcasts || 0}
                                        </p>
                                    </div>
                                </div>
                            </Card>

                            <Card>
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <FaChartLine className="h-8 w-8 text-purple-600" />
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-500">
                                            {t('telegram.groupBroadcast.successfullySent')}
                                        </p>
                                        <p className="text-2xl font-semibold text-gray-900">
                                            {broadcastStats?.successfully_sent || 0}
                                        </p>
                                    </div>
                                </div>
                            </Card>

                            <Card>
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <FaGlobe className="h-8 w-8 text-orange-600" />
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-500">
                                            {t('telegram.groupBroadcast.thisWeekBroadcasts')}
                                        </p>
                                        <p className="text-2xl font-semibold text-gray-900">
                                            {broadcastStats?.this_week_broadcasts || 0}
                                        </p>
                                    </div>
                                </div>
                            </Card>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
