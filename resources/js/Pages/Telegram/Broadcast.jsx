import React, { useState, useEffect } from "react";
import { Head, useForm, router } from "@inertiajs/react";
import { Button, Card, Select, Textarea, Input } from "@/Components/UI";
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
} from "react-icons/fa";

export default function TelegramBroadcast({ stats }) {
    const [activeTab, setActiveTab] = useState("broadcast");
    const [broadcasts, setBroadcasts] = useState(null);
    const [broadcastStats, setBroadcastStats] = useState(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        message: "",
        target: "active",
        image: null,
        keyboard: [],
    });

    const handleBroadcast = (e) => {
        e.preventDefault();
        post("/telegram/broadcast", {
            onSuccess: () => {
                reset("message");
            },
        });
    };

    const loadBroadcastHistory = () => {
        // 通过 Inertia 获取广播历史数据
        router.get('/telegram/broadcast', {
            tab: 'history'
        }, {
            preserveState: true,
            onSuccess: (page) => {
                if (page.props.broadcasts) {
                    setBroadcasts(page.props.broadcasts);
                }
                if (page.props.broadcastStats) {
                    setBroadcastStats(page.props.broadcastStats);
                }
            }
        });
    };

    return (
        <>
            <Head title="消息管理" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">
                            消息管理
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            发送群发消息和管理消息模板
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
                                    群发消息
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
                                    广播历史
                                </button>
                                <button
                                    onClick={() => setActiveTab("stats")}
                                    className={`py-2 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === "stats"
                                            ? "border-blue-500 text-blue-600"
                                            : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                                    }`}
                                >
                                    发送统计
                                </button>
                            </nav>
                        </div>
                    </div>

                    {/* 群发消息 */}
                    {activeTab === "broadcast" && (
                        <Card title="群发消息">
                            <form
                                onSubmit={handleBroadcast}
                                className="space-y-6"
                            >
                                <Select
                                    label="目标用户"
                                    id="target"
                                    value={data.target}
                                    onChange={(e) =>
                                        setData("target", e.target.value)
                                    }
                                    options={[
                                        {
                                            value: "active",
                                            label: `活跃用户 (${
                                                stats?.available_targets
                                                    ?.active || 0
                                            })`,
                                        },
                                        {
                                            value: "all",
                                            label: `所有用户 (${
                                                stats?.available_targets?.all ||
                                                0
                                            })`,
                                        },
                                        {
                                            value: "recent",
                                            label: `最近7天活跃 (${
                                                stats?.available_targets
                                                    ?.recent_7_days || 0
                                            })`,
                                        },
                                        {
                                            value: "recent_30",
                                            label: `最近30天活跃 (${
                                                stats?.available_targets
                                                    ?.recent_30_days || 0
                                            })`,
                                        },
                                    ]}
                                />

                                <Textarea
                                    label="消息内容"
                                    id="message"
                                    value={data.message}
                                    onChange={(e) =>
                                        setData("message", e.target.value)
                                    }
                                    placeholder="输入要发送的消息..."
                                    rows={6}
                                    required
                                    error={errors.message}
                                />

                                {/* 图片上传 */}
                                <div className="space-y-2">
                                    <label className="block text-sm font-medium text-gray-700">
                                        图片上传
                                    </label>
                                    <div className="flex items-center space-x-4">
                                        <input
                                            type="file"
                                            accept="image/*"
                                            onChange={(e) => {
                                                const file = e.target.files[0];
                                                if (file) {
                                                    setData("image", file);
                                                }
                                            }}
                                            className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        />
                                        {data.image && (
                                            <Button
                                                type="button"
                                                variant="danger"
                                                size="sm"
                                                onClick={() => setData("image", null)}
                                            >
                                                <FaTrash className="w-3 h-3" />
                                            </Button>
                                        )}
                                    </div>
                                    {data.image && (
                                        <div className="mt-2">
                                            <img
                                                src={URL.createObjectURL(data.image)}
                                                alt="预览"
                                                className="max-w-xs rounded-lg border"
                                            />
                                        </div>
                                    )}
                                </div>

                                {/* 键盘配置 */}
                                <div className="space-y-2">
                                    <label className="block text-sm font-medium text-gray-700">
                                        键盘按钮
                                    </label>
                                    <div className="space-y-2">
                                        {data.keyboard.map((row, rowIndex) => (
                                            <div key={rowIndex} className="flex items-center space-x-2">
                                                {row.map((button, buttonIndex) => (
                                                    <div key={buttonIndex} className="flex items-center space-x-1">
                                                        <Input
                                                            placeholder="按钮文字"
                                                            value={button.text || ""}
                                                            onChange={(e) => {
                                                                const newKeyboard = [...data.keyboard];
                                                                newKeyboard[rowIndex][buttonIndex].text = e.target.value;
                                                                setData("keyboard", newKeyboard);
                                                            }}
                                                            className="w-32"
                                                        />
                                                        <Input
                                                            placeholder="回调数据或URL"
                                                            value={button.callback_data || button.url || ""}
                                                            onChange={(e) => {
                                                                const newKeyboard = [...data.keyboard];
                                                                if (e.target.value.startsWith('http')) {
                                                                    newKeyboard[rowIndex][buttonIndex].url = e.target.value;
                                                                    delete newKeyboard[rowIndex][buttonIndex].callback_data;
                                                                } else {
                                                                    newKeyboard[rowIndex][buttonIndex].callback_data = e.target.value;
                                                                    delete newKeyboard[rowIndex][buttonIndex].url;
                                                                }
                                                                setData("keyboard", newKeyboard);
                                                            }}
                                                            className="w-40"
                                                        />
                                                        <Button
                                                            type="button"
                                                            variant="danger"
                                                            size="sm"
                                                            onClick={() => {
                                                                const newKeyboard = [...data.keyboard];
                                                                newKeyboard[rowIndex].splice(buttonIndex, 1);
                                                                if (newKeyboard[rowIndex].length === 0) {
                                                                    newKeyboard.splice(rowIndex, 1);
                                                                }
                                                                setData("keyboard", newKeyboard);
                                                            }}
                                                        >
                                                            <FaTrash className="w-3 h-3" />
                                                        </Button>
                                                    </div>
                                                ))}
                                                <Button
                                                    type="button"
                                                    variant="secondary"
                                                    size="sm"
                                                    onClick={() => {
                                                        const newKeyboard = [...data.keyboard];
                                                        newKeyboard[rowIndex].push({ text: "", callback_data: "" });
                                                        setData("keyboard", newKeyboard);
                                                    }}
                                                >
                                                    <FaPlus className="w-3 h-3" />
                                                </Button>
                                            </div>
                                        ))}
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            onClick={() => {
                                                setData("keyboard", [...data.keyboard, [{ text: "", callback_data: "" }]]);
                                            }}
                                        >
                                            <FaPlus className="w-3 h-3 mr-1" />
                                            添加按钮行
                                        </Button>
                                    </div>
                                </div>

                                <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                    <div className="flex">
                                        <div className="flex-shrink-0">
                                            <FaExclamationTriangle className="h-5 w-5 text-yellow-400" />
                                        </div>
                                        <div className="ml-3">
                                            <p className="text-sm text-yellow-700">
                                                消息将通过队列异步发送，请确保队列处理器正在运行。
                                            </p>
                                            <p className="text-sm text-yellow-600 mt-1">
                                                发送统计将在消息实际发送完成后更新，包括用户block等失败情况。失败的消息会自动重试最多3次。
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <Button
                                    type="submit"
                                    loading={processing}
                                    variant="primary"
                                    size="lg"
                                    className="inline-flex items-center space-x-2"
                                >
                                    <FaPaperPlane className="w-4 h-4" />
                                    <span>发送消息</span>
                                </Button>
                            </form>
                        </Card>
                    )}



                    {/* 发送统计 */}
                    {activeTab === "stats" && (
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <div className="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                                            <FaUsers className="text-white text-sm" />
                                        </div>
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-500">
                                            总用户数
                                        </p>
                                        <p className="text-2xl font-bold text-gray-900">
                                            {stats?.available_targets?.all || 0}
                                        </p>
                                    </div>
                                </div>
                            </Card>

                            <Card>
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <div className="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                                            <FaChartLine className="text-white text-sm" />
                                        </div>
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-500">
                                            活跃用户
                                        </p>
                                        <p className="text-2xl font-bold text-gray-900">
                                            {stats?.available_targets?.active ||
                                                0}
                                        </p>
                                    </div>
                                </div>
                            </Card>

                            <Card>
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <div className="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center">
                                            <FaChartLine className="text-white text-sm" />
                                        </div>
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-500">
                                            7天活跃
                                        </p>
                                        <p className="text-2xl font-bold text-gray-900">
                                            {stats?.available_targets
                                                ?.recent_7_days || 0}
                                        </p>
                                    </div>
                                </div>
                            </Card>

                            <Card>
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <div className="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center">
                                            <FaGlobe className="text-white text-sm" />
                                        </div>
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-500">
                                            已选语言
                                        </p>
                                        <p className="text-2xl font-bold text-gray-900">
                                            {stats?.users_with_language_selected ||
                                                0}
                                        </p>
                                    </div>
                                </div>
                            </Card>
                        </div>
                    )}

                    {/* 广播历史 */}
                    {activeTab === "history" && broadcasts && (
                        <div className="space-y-6">
                            {/* 统计卡片 */}
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <Card>
                                    <div className="flex items-center">
                                        <FaHistory className="w-8 h-8 text-blue-500" />
                                        <div className="ml-4">
                                            <p className="text-sm text-gray-600">总广播数</p>
                                            <p className="text-2xl font-bold">{broadcastStats?.total_broadcasts || 0}</p>
                                        </div>
                                    </div>
                                </Card>

                                <Card>
                                    <div className="flex items-center">
                                        <FaPaperPlane className="w-8 h-8 text-green-500" />
                                        <div className="ml-4">
                                            <p className="text-sm text-gray-600">成功发送</p>
                                            <p className="text-2xl font-bold">{broadcastStats?.total_sent || 0}</p>
                                        </div>
                                    </div>
                                </Card>

                                <Card>
                                    <div className="flex items-center">
                                        <FaUsers className="w-8 h-8 text-orange-500" />
                                        <div className="ml-4">
                                            <p className="text-sm text-gray-600">成功率</p>
                                            <p className="text-2xl font-bold">{broadcastStats?.success_rate || 0}%</p>
                                        </div>
                                    </div>
                                </Card>

                                <Card>
                                    <div className="flex items-center">
                                        <FaChartLine className="w-8 h-8 text-purple-500" />
                                        <div className="ml-4">
                                            <p className="text-sm text-gray-600">本周广播</p>
                                            <p className="text-2xl font-bold">{broadcastStats?.recent_broadcasts || 0}</p>
                                        </div>
                                    </div>
                                </Card>
                            </div>

                            {/* 广播历史列表 */}
                            <Card title="广播历史">
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    消息内容
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    目标
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    发送统计
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    状态
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    发送时间
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {broadcasts.data.map((broadcast) => (
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
                                                                    图片
                                                                </span>
                                                            )}
                                                            {broadcast.keyboard && (
                                                                <span className="inline-flex items-center text-xs text-gray-500">
                                                                    <FaKeyboard className="w-3 h-3 mr-1" />
                                                                    键盘
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">
                                                        {broadcast.target === 'all' && '所有用户'}
                                                        {broadcast.target === 'active' && '活跃用户'}
                                                        {broadcast.target === 'recent' && '最近7天活跃'}
                                                        {broadcast.target === 'recent_30' && '最近30天活跃'}
                                                        {broadcast.target === 'inactive' && '非活跃用户'}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">
                                                        <div>成功: <span className="font-medium text-green-600">{broadcast.sent_count}</span></div>
                                                        <div>失败: <span className="font-medium text-red-600">{broadcast.failed_count}</span></div>
                                                        <div>总计: <span className="font-medium">{broadcast.total_users}</span></div>
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
                                                            {broadcast.status === 'completed' ? '发送完成' :
                                                             broadcast.status === 'completed_with_errors' ? '部分成功' :
                                                             broadcast.status === 'pending' ? '发送中(含重试)...' : '发送失败'}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">
                                                        {new Date(broadcast.created_at).toLocaleString()}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {broadcasts.data.length === 0 && (
                                    <div className="text-center py-8">
                                        <p className="text-gray-500">暂无广播记录</p>
                                    </div>
                                )}
                            </Card>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
