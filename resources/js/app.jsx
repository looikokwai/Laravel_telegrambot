import './bootstrap';
import './i18n'; // 导入 i18n 配置
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import AppLayout from './Layouts/AppLayout';

createInertiaApp({
    title: (title) => `${title}`,
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        const page = pages[`./Pages/${name}.jsx`];

        // 检查页面是否存在
        if (!page) {
            console.error(`Page not found: ${name}`);
            throw new Error(`Page not found: ${name}`);
        }

        // 检查页面是否有默认导出
        if (!page.default) {
            console.error(`Page has no default export: ${name}`);
            throw new Error(`Page has no default export: ${name}`);
        }

        // 只有Login页面是独立的，其他页面都使用AppLayout
        const pagesWithoutLayout = ['Auth/Login'];

        if (!pagesWithoutLayout.includes(name)) {
            page.default.layout = (pageComponent) => <AppLayout>{pageComponent}</AppLayout>;
        }

        return page;
    },
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
