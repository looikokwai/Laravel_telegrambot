import React, { useState, useEffect } from "react";
import { Head, useForm, router } from "@inertiajs/react";
import { Button, Card, Select, Textarea, Input } from "@/Components/UI";
import { useTranslation } from 'react-i18next';
import {
    FaPaperPlane,
    FaBroadcastTower,
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

export default function ChannelBroadcast({ channels = [], stats = null, broadcasts = null }) {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState("broadcast");
    const [broadcastHistory, setBroadcastHistory] = useState(broadcasts);
    const [broadcastStats, setBroadcastStats] = useState(stats);

    const { data, setData, post, processing, errors, reset } = useForm({
        message: "",
        target_channels: [],
        target_type: "selected",
        image: null,
        keyboard: [],
    });

    const handleBroadcast = (e) => {
        e.preventDefault();
        post("/telegram/channel-broadcast", {
            onSuccess: () => {
                reset("message");
            },
        });
    };

    const loadBroadcastHistory = () => {
        // 通过 Inertia 获取广播历史数据
        router.get('/telegram/channel-broadcast', {
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
            pending: { color: 'bg-yellow-100 text-yellow-800', text: t('telegram.channelBroadcast.pending') },
            completed: { color: 'bg-green-100 text-green-800', text: t('telegram.channelBroadcast.completed') },
            completed_with_errors: { color: 'bg-orange-100 text-orange-800', text: t('telegram.channelBroadcast.completedWithErrors') },
            failed: { color: 'bg-red-100 text-red-800', text: t('telegram.channelBroadcast.failed') }
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

    // 安全地处理频道数据
    const allChannels = channels || [];

    return (
        <>
            <Head title={t('telegram.channelBroadcast.channelBroadcast')} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">
                            {t('telegram.channelBroadcast.channelBroadcast')}
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            {t('telegram.channelBroadcast.channelBroadcastSubtitle')}
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
                                    {t('telegram.channelBroadcast.broadcastMessage')}
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
                                    {t('telegram.channelBroadcast.broadcastHistory')}
                                </button>
                                <button
                                    onClick={() => setActiveTab("stats")}
                                    className={`py-2 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === "stats"
                                            ? "border-blue-500 text-blue-600"
                                            : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                                    }`}
                                >
                                    {t('telegram.channelBroadcast.stats')}
                                </button>
                            </nav>
                        </div>
                    </div>

                    {/* 广播消息标签页 */}
                    {activeTab === "broadcast" && (
                        <Card>
                            <form onSubmit={handleBroadcast} className="space-y-6">
                                {/* 目标频道选择 */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {t('telegram.channelBroadcast.targetChannels')}
                                    </label>
                                    <Select
                                        value={data.target_type}
                                        onChange={(e) => setData('target_type', e.target.value)}
                                        className="w-full"
                                        options={[
                                            { value: 'selected', label: t('telegram.channelBroadcast.selectedChannels') },
                                            { value: 'all', label: t('telegram.channelBroadcast.allChannels') },
                                            { value: 'active', label: t('telegram.channelBroadcast.activeChannels') }
                                        ]}
                                    />
                                </div>

                                {/* 频道选择（当选择"selected"时显示） */}
                                {data.target_type === "selected" && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            {t('telegram.channelBroadcast.selectChannels')}
                                        </label>
                                        <div className="max-h-60 overflow-y-auto border border-gray-300 rounded-md p-4">
                                            {allChannels.length === 0 ? (
                                                <p className="text-gray-500 text-sm">{t('telegram.channels.noChannelsFound')}</p>
                                            ) : (
                                                <div className="space-y-2">
                                                    {allChannels.map((channel) => (
                                                        <label key={channel.id} className="flex items-center">
                                                            <input
                                                                type="checkbox"
                                                                value={channel.id}
                                                                checked={data.target_channels.includes(channel.id)}
                                                                onChange={(e) => {
                                                                    const newChannels = e.target.checked
                                                                        ? [...data.target_channels, channel.id]
                                                                        : data.target_channels.filter(id => id !== channel.id);
                                                                    setData('target_channels', newChannels);
                                                                }}
                                                                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                            />
                                                            <span className="ml-2 text-sm text-gray-900">
                                                                {channel.title} ({channel.channel_id})
                                                            </span>
                                                        </label>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                        {errors.target_channels && (
                                            <p className="mt-1 text-sm text-red-600">{errors.target_channels}</p>
                                        )}
                                    </div>
                                )}

                                {/* 消息内容 */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {t('telegram.channelBroadcast.messageContent')}
                                    </label>
                                    <Textarea
                                        value={data.message}
                                        onChange={(e) => setData('message', e.target.value)}
                                        placeholder={t('telegram.channelBroadcast.enterMessage')}
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
                                        {t('telegram.channelBroadcast.imageUpload')} ({t('common.optional')})
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
                                                {t('telegram.channelBroadcast.queueWarning')}
                                            </h3>
                                            <div className="mt-2 text-sm text-yellow-700">
                                                <p>{t('telegram.channelBroadcast.queueWarningDetail')}</p>
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
                                        <span>{t('telegram.channelBroadcast.sendMessage')}</span>
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
                                                            {t('telegram.channelBroadcast.messageContent')}
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            {t('telegram.channelBroadcast.targetChannels')}
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            {t('telegram.channelBroadcast.sendStats')}
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            {t('telegram.channelBroadcast.status')}
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            {t('telegram.channelBroadcast.sendTime')}
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
                                                                            {t('telegram.channelBroadcast.image')}
                                                                        </span>
                                                                    )}
                                                                    {broadcast.keyboard && (
                                                                        <span className="inline-flex items-center text-xs text-gray-500">
                                                                            <FaKeyboard className="w-3 h-3 mr-1" />
                                                                            {t('telegram.channelBroadcast.keyboard')}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </td>
                                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                                {broadcast.target_type === 'all' && t('telegram.channelBroadcast.allChannels')}
                                                                {broadcast.target_type === 'selected' && t('telegram.channelBroadcast.selectedChannels')}
                                                                {broadcast.target_type === 'active' && t('telegram.channelBroadcast.activeChannels')}
                                                            </td>
                                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                                <div>{t('telegram.channelBroadcast.total')}: <span className="font-medium">{broadcast.total_channels}</span></div>
                                                                <div>{t('telegram.channelBroadcast.sent')}: <span className="font-medium text-green-600">{broadcast.sent_count || 0}</span></div>
                                                                <div>{t('telegram.channelBroadcast.failed')}: <span className="font-medium text-red-600">{broadcast.failed_count || 0}</span></div>
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
                                                                    {broadcast.status === 'completed' ? t('telegram.channelBroadcast.completed') :
                                                                     broadcast.status === 'completed_with_errors' ? t('telegram.channelBroadcast.completedWithErrors') :
                                                                     broadcast.status === 'pending' ? t('telegram.channelBroadcast.pending') : t('telegram.channelBroadcast.failed')}
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
                                            {t('telegram.channelBroadcast.noBroadcastRecords')}
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
                                        <FaBroadcastTower className="h-8 w-8 text-purple-600" />
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-500">
                                            {t('telegram.channelBroadcast.totalChannels')}
                                        </p>
                                        <p className="text-2xl font-semibold text-gray-900">
                                            {stats?.total_channels || 0}
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
                                            {t('telegram.channelBroadcast.totalBroadcasts')}
                                        </p>
                                        <p className="text-2xl font-semibold text-gray-900">
                                            {stats?.total_broadcasts || 0}
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
                                            {t('telegram.channelBroadcast.successfullySent')}
                                        </p>
                                        <p className="text-2xl font-semibold text-gray-900">
                                            {stats?.successfully_sent || 0}
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
                                            {t('telegram.channelBroadcast.thisWeekBroadcasts')}
                                        </p>
                                        <p className="text-2xl font-semibold text-gray-900">
                                            {stats?.this_week_broadcasts || 0}
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
