import { Link, usePage, router } from "@inertiajs/react";
import { Toaster, toast } from "react-hot-toast";
import { useEffect, useState } from "react";
import { Button } from "@/Components/UI";
import { useTranslation } from 'react-i18next';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import {
    FaTachometerAlt,
    FaUsers,
    FaUser,
    FaComments,
    FaBars,
    FaTimes,
    FaLanguage,
    FaTerminal,
    FaMousePointer,
    FaGlobe,
    FaChartBar,
    FaRobot,
    FaBroadcastTower,
    FaList,
    FaImages,
} from "react-icons/fa";

// FlashMessages component for handling Laravel flash messages
function FlashMessages() {
    const { flash } = usePage().props;

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
        if (flash?.info) {
            toast(flash.info);
        }
        if (flash?.warning) {
            toast(flash.warning, {
                icon: "⚠️",
            });
        }
    }, [flash]);

    return null;
}

export default function AppLayout({ children, className = "" }) {
    const { auth, url } = usePage().props;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const { t } = useTranslation();

    // 确保 url 存在，如果不存在则使用空字符串
    const currentUrl = url || '';

    const navigationItems = [
        {
            name: t('navigation.overview'),
            href: "/dashboard",
            page: "Dashboard",
            icon: <FaChartBar className="w-4 h-4" />,
            current: currentUrl === "/dashboard",
        },
        {
            name: t('navigation.telegramBot'),
            href: "/telegram",
            page: "Telegram",
            icon: <FaRobot className="w-4 h-4" />,
            current: currentUrl.startsWith("/telegram"),
            children: [
                {
                    name: t('navigation.userManagement'),
                    href: "/telegram/users",
                    icon: <FaUsers className="w-4 h-4" />,
                    current: currentUrl === "/telegram/users",
                },
                {
                    name: t('navigation.messageBroadcast'),
                    href: "/telegram/broadcast",
                    icon: <FaBroadcastTower className="w-4 h-4" />,
                    current: currentUrl === "/telegram/broadcast",
                },
                {
                    name: t('navigation.groupManagement'),
                    href: "/telegram/group-management",
                    icon: <FaUsers className="w-4 h-4" />,
                    current: currentUrl === "/telegram/group-management",
                },
                {
                    name: t('navigation.groupBroadcast'),
                    href: "/telegram/group-broadcast",
                    icon: <FaBroadcastTower className="w-4 h-4" />,
                    current: currentUrl === "/telegram/group-broadcast",
                },
                {
                    name: t('navigation.channelManagement'),
                    href: "/telegram/channel-management",
                    icon: <FaUsers className="w-4 h-4" />,
                    current: currentUrl === "/telegram/channel-management",
                },
                {
                    name: t('navigation.channelBroadcast'),
                    href: "/telegram/channel-broadcast",
                    icon: <FaBroadcastTower className="w-4 h-4" />,
                    current: currentUrl === "/telegram/channel-broadcast",
                },
                {
                    name: t('navigation.menuManagement'),
                    href: "/telegram/menu-management",
                    icon: <FaList className="w-4 h-4" />,
                    current: currentUrl === "/telegram/menu-management",
                },
                {
                    name: t('navigation.imageManagement'),
                    href: "/telegram/image-management",
                    icon: <FaImages className="w-4 h-4" />,
                    current: currentUrl === "/telegram/image-management",
                },
                {
                    name: t('navigation.languageManagement'),
                    href: "/telegram/language-management",
                    icon: <FaLanguage className="w-4 h-4" />,
                    current: currentUrl === "/telegram/language-management",
                },
            ],
        },
        {
            name: t('navigation.profile'),
            href: "/profile",
            page: "Auth/Profile",
            icon: <FaUser className="w-4 h-4" />,
            current: currentUrl === "/profile",
        },
    ];

    return (
        <>
            <div className={`min-h-screen bg-gray-50 ${className}`}>
                {/* 顶部导航栏 */}
                <nav className="bg-white shadow-sm border-b border-gray-200">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex">
                                <div className="flex-shrink-0 flex items-center">
                                    <Link
                                        href="/dashboard"
                                        className="text-xl font-bold text-gray-900"
                                    >
                                        {t('common.appManagement')}
                                    </Link>
                                </div>
                                <div className="hidden sm:ml-6 sm:flex sm:items-center sm:space-x-8">
                                    {navigationItems.map((item) => (
                                        item.children ? (
                                            <div key={item.name} className="relative group">
                                                <Link
                                                    href={item.href}
                                                    className={`${
                                                        item.current
                                                            ? "border-blue-500 text-gray-900"
                                                            : "border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700"
                                                    } inline-flex items-center px-3 py-4 border-b-2 text-sm font-medium space-x-2 h-16`}
                                                >
                                                    {item.icon}
                                                    <span>{item.name}</span>
                                                </Link>
                                                <div className="absolute left-0 mt-0 w-56 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 border border-gray-200">
                                                    <div className="py-1">
                                                        {item.children.map((child) => (
                                                            <Link
                                                                key={child.name}
                                                                href={child.href}
                                                                className={`${
                                                                    child.current
                                                                        ? "bg-blue-50 text-blue-700"
                                                                        : "text-gray-700 hover:bg-gray-50"
                                                                } flex items-center px-4 py-2 text-sm space-x-3`}
                                                            >
                                                                {child.icon}
                                                                <span>{child.name}</span>
                                                            </Link>
                                                        ))}
                                                    </div>
                                                </div>
                                            </div>
                                        ) : (
                                            <Link
                                                key={item.name}
                                                href={item.href}
                                                className={`${
                                                    item.current
                                                        ? "border-blue-500 text-gray-900"
                                                        : "border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700"
                                                } inline-flex items-center px-3 py-4 border-b-2 text-sm font-medium space-x-2 h-16`}
                                            >
                                                {item.icon}
                                                <span>{item.name}</span>
                                            </Link>
                                        )
                                    ))}
                                </div>
                            </div>
                            <div className="hidden sm:ml-6 sm:flex sm:items-center space-x-4">
                                <LanguageSwitcher />
                                <span className="text-sm text-gray-700">
                                    {t('common.welcome')}，{auth?.user?.name}
                                </span>
                                <Button
                                    onClick={() => router.post("/logout")}
                                    variant="outline"
                                    size="sm"
                                >
                                    {t('common.logout')}
                                </Button>
                            </div>

                            {/* 移动端菜单按钮 */}
                            <div className="sm:hidden flex items-center">
                                <button
                                    onClick={() =>
                                        setMobileMenuOpen(!mobileMenuOpen)
                                    }
                                    className="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                                >
                                    {mobileMenuOpen ? (
                                        <FaTimes className="h-6 w-6" />
                                    ) : (
                                        <FaBars className="h-6 w-6" />
                                    )}
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* 移动端菜单 */}
                    {mobileMenuOpen && (
                        <div className="sm:hidden">
                            <div className="pt-2 pb-3 space-y-1">
                                {navigationItems.map((item) => (
                                    <div key={item.name}>
                                        <Link
                                            href={item.href}
                                            className={`${
                                                item.current
                                                    ? "bg-blue-50 border-blue-500 text-blue-700"
                                                    : "border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700"
                                            } flex items-center pl-3 pr-4 py-2 border-l-4 text-base font-medium space-x-3`}
                                            onClick={() => setMobileMenuOpen(false)}
                                        >
                                            {item.icon}
                                            <span>{item.name}</span>
                                        </Link>
                                        {item.children && (
                                            <div className="ml-6 space-y-1">
                                                {item.children.map((child) => (
                                                    <Link
                                                        key={child.name}
                                                        href={child.href}
                                                        className={`${
                                                            child.current
                                                                ? "bg-blue-50 border-blue-500 text-blue-700"
                                                                : "border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700"
                                                        } flex items-center pl-3 pr-4 py-2 border-l-4 text-sm font-medium space-x-3`}
                                                        onClick={() => setMobileMenuOpen(false)}
                                                    >
                                                        {child.icon}
                                                        <span>{child.name}</span>
                                                    </Link>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                            <div className="pt-4 pb-3 border-t border-gray-200">
                                <div className="flex items-center px-4">
                                    <div className="flex-shrink-0">
                                        <div className="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span className="text-sm font-medium text-gray-700">
                                                {auth?.user?.name?.[0]?.toUpperCase()}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="ml-3">
                                        <div className="text-base font-medium text-gray-800">
                                            {auth?.user?.name}
                                        </div>
                                        <div className="text-sm font-medium text-gray-500">
                                            {auth?.user?.email}
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-3 px-4">
                                    <Button
                                        onClick={() => router.post("/logout")}
                                        variant="outline"
                                        size="sm"
                                        className="w-full"
                                    >
                                        登出
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}
                </nav>

                {/* 主内容区域 */}
                <main>{children}</main>
            </div>

            {/* Toast notifications */}
            <Toaster
                position="top-right"
                toastOptions={{
                    duration: 4000,
                    style: {
                        background: "#363636",
                        color: "#fff",
                    },
                    success: {
                        duration: 3000,
                        iconTheme: {
                            primary: "#4ade80",
                            secondary: "#fff",
                        },
                    },
                    error: {
                        duration: 4000,
                        iconTheme: {
                            primary: "#ef4444",
                            secondary: "#fff",
                        },
                    },
                }}
            />

            {/* Flash messages handler */}
            <FlashMessages />
        </>
    );
}
