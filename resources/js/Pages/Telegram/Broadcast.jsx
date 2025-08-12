import React, { useState } from "react";
import { Head, useForm } from "@inertiajs/react";
import { Button, Card, Select, Textarea, Input } from "@/Components/UI";
import {
    FaPaperPlane,
    FaUsers,
    FaChartLine,
    FaGlobe,
    FaExclamationTriangle,
    FaInfoCircle,
} from "react-icons/fa";

export default function TelegramBroadcast({ stats }) {
    const [activeTab, setActiveTab] = useState("broadcast");

    const { data, setData, post, processing, errors, reset } = useForm({
        message: "",
        target: "active",
    });

    const handleBroadcast = (e) => {
        e.preventDefault();
        post("/telegram/broadcast", {
            onSuccess: () => {
                reset("message");
            },
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

                                <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                    <div className="flex">
                                        <div className="flex-shrink-0">
                                            <FaExclamationTriangle className="h-5 w-5 text-yellow-400" />
                                        </div>
                                        <div className="ml-3">
                                            <p className="text-sm text-yellow-700">
                                                消息将通过队列异步发送，请确保队列处理器正在运行。
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
                </div>
            </div>
        </>
    );
}
