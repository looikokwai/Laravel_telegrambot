import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button, Input, Card } from '@/Components/UI';
import { useTranslation } from 'react-i18next';
import LanguageSwitcher from '@/Components/LanguageSwitcher';

export default function Login() {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title={t('auth.login')} />

            <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
                {/* 语言切换器 - 右上角桌面版，顶部居中移动版 */}
                <div className="absolute top-4 right-4 sm:right-4 sm:left-auto left-1/2 sm:left-auto transform sm:transform-none -translate-x-1/2 sm:translate-x-0">
                    <LanguageSwitcher />
                </div>

                <div className="max-w-md w-full space-y-8">
                    <div>
                        <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                            {t('auth.loginTitle')}
                        </h2>
                        <p className="mt-2 text-center text-sm text-gray-600">
                            {t('auth.loginSubtitle')}
                        </p>
                    </div>

                    <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
                        <div className="space-y-4">
                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
                                    {t('auth.emailAddress')}
                                </label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    autoComplete="email"
                                    required
                                    placeholder={t('auth.enterEmail')}
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    error={errors.email}
                                />
                            </div>

                            <div>
                                <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">
                                    {t('auth.password')}
                                </label>
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autoComplete="current-password"
                                    required
                                    placeholder={t('auth.enterPassword')}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    error={errors.password}
                                />
                            </div>
                        </div>

                        <div className="flex items-center justify-between">
                            <div className="flex items-center">
                                <input
                                    id="remember"
                                    name="remember"
                                    type="checkbox"
                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                />
                                <label htmlFor="remember" className="ml-2 block text-sm text-gray-900">
                                    {t('auth.rememberMe')}
                                </label>
                            </div>
                        </div>

                        <div>
                            <Button
                                type="submit"
                                loading={processing}
                                className="w-full"
                                size="lg"
                            >
                                {t('auth.signIn')}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
