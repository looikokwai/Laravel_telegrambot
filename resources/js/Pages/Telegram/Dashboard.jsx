import React from "react";
import { Head, Link } from "@inertiajs/react";
import { Button, Card } from "@/Components/UI";
import { FaUsers, FaChartLine, FaGlobe } from "react-icons/fa";

export default function TelegramDashboard({ stats, recentUsers }) {
    return (
        <>
            <Head title="Telegram Bot 管理" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">
                            概览
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Telegram Bot 管理面板概览
                        </p>
                    </div>

                    {/* 统计卡片 */}
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                        <Card padding="default">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                        <FaUsers className="text-white text-lg" />
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

                        <Card padding="default">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                        <FaChartLine className="text-white text-lg" />
                                    </div>
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-500">
                                        活跃用户
                                    </p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {stats?.available_targets?.active || 0}
                                    </p>
                                </div>
                            </div>
                        </Card>

                        <Card padding="default">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
                                        <FaChartLine className="text-white text-lg" />
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

                        <Card padding="default">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                        <FaGlobe className="text-white text-lg" />
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


                    {/* 最近用户 */}
                    <Card title="最近用户" padding="none">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            用户
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            语言
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            状态
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            最后活跃
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {recentUsers?.slice(0, 5).map((user) => (
                                        <tr
                                            key={user.id}
                                            className="hover:bg-gray-50"
                                        >
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <div className="flex-shrink-0 h-8 w-8">
                                                        <div className="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                            <span className="text-sm font-medium text-gray-700">
                                                                {(user.first_name ||
                                                                    "U")[0].toUpperCase()}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="ml-4">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {user.first_name ||
                                                                "Unknown"}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            @
                                                            {user.username ||
                                                                "no_username"}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="inline-flex px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-md">
                                                    {user.language || "en"}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                                        user.is_active
                                                            ? "bg-green-100 text-green-800"
                                                            : "bg-red-100 text-red-800"
                                                    }`}
                                                >
                                                    {user.is_active
                                                        ? "活跃"
                                                        : "非活跃"}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {user.last_interaction
                                                    ? new Date(
                                                          user.last_interaction
                                                      ).toLocaleString("zh-CN")
                                                    : "从未"}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="px-6 py-3 bg-gray-50 border-t border-gray-200">
                            <Button
                                as={Link}
                                href="/telegram/users"
                                variant="outline"
                                size="sm"
                            >
                                查看所有用户
                            </Button>
                        </div>
                    </Card>
                </div>
            </div>
        </>
    );
}
