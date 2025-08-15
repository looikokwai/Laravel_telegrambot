import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button, Input, Card } from '@/Components/UI';
import { useTranslation } from 'react-i18next';

export default function Profile({ user }) {
    const { t } = useTranslation();

    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        name: user.name,
        email: user.email,
    });

    const { data: passwordData, setData: setPasswordData, put: putPassword, processing: passwordProcessing, errors: passwordErrors, recentlySuccessful: passwordSuccess } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleProfileUpdate = (e) => {
        e.preventDefault();
        put('/profile');
    };

    const handlePasswordUpdate = (e) => {
        e.preventDefault();
        putPassword('/profile/password', {
            onSuccess: () => {
                setPasswordData({
                    current_password: '',
                    password: '',
                    password_confirmation: '',
                });
            }
        });
    };

    return (
        <>
            <Head title={t('auth.profileTitle')} />

            <div className="py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-3xl mx-auto">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">{t('auth.profileTitle')}</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            {t('auth.profileSubtitle')}
                        </p>
                    </div>

                    <div className="space-y-6">
                        <Card title={t('auth.basicInfo')}>

                            <form onSubmit={handleProfileUpdate} className="space-y-6">
                                <Input
                                    label={t('auth.name')}
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    error={errors.name}
                                    required
                                />

                                <Input
                                    label={t('auth.email')}
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    error={errors.email}
                                    required
                                />

                                <div className="flex items-center justify-between">
                                    <Button
                                        type="submit"
                                        loading={processing}
                                        variant="primary"
                                    >
                                        {t('auth.updateProfile')}
                                    </Button>

                                    {recentlySuccessful && (
                                        <p className="text-sm text-green-600">✅ {t('auth.profileUpdated')}</p>
                                    )}
                                </div>
                            </form>
                        </Card>

                        <Card title={t('auth.updatePassword')}>
                            <form onSubmit={handlePasswordUpdate} className="space-y-6">
                                <Input
                                    label={t('auth.currentPassword')}
                                    id="current_password"
                                    type="password"
                                    value={passwordData.current_password}
                                    onChange={(e) => setPasswordData('current_password', e.target.value)}
                                    error={passwordErrors.current_password}
                                    required
                                />

                                <Input
                                    label={t('auth.newPassword')}
                                    id="password"
                                    type="password"
                                    value={passwordData.password}
                                    onChange={(e) => setPasswordData('password', e.target.value)}
                                    error={passwordErrors.password}
                                    required
                                />

                                <Input
                                    label={t('auth.confirmNewPassword')}
                                    id="password_confirmation"
                                    type="password"
                                    value={passwordData.password_confirmation}
                                    onChange={(e) => setPasswordData('password_confirmation', e.target.value)}
                                    error={passwordErrors.password_confirmation}
                                    required
                                />

                                <div className="flex items-center justify-between">
                                    <Button
                                        type="submit"
                                        loading={passwordProcessing}
                                        variant="primary"
                                    >
                                        {t('auth.updatePassword')}
                                    </Button>

                                    {passwordSuccess && (
                                        <p className="text-sm text-green-600">✅ {t('auth.passwordUpdated')}</p>
                                    )}
                                </div>
                            </form>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}
