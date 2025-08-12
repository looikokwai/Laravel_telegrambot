import { Link, usePage } from '@inertiajs/react';
import { FaSearch, FaBullseye, FaMobileAlt, FaLaptopCode } from 'react-icons/fa'; // 导入所需的 React 图标

export default function ServicesNavigation() {
    const { url } = usePage();

    const services = [
        {
            name: 'SEO',
            href: '/services/seo',
            icon: FaSearch,
            color: 'blue'
        },
        {
            name: 'SEM',
            href: '/services/sem',
            icon: FaBullseye,
            color: 'green'
        },
        {
            name: 'Social Media',
            href: '/services/social-media',
            icon: FaMobileAlt,
            color: 'purple'
        },
        {
            name: 'Web Development',
            href: '/services/web-development',
            icon: FaLaptopCode,
            color: 'indigo'
        }
    ];

    const getColorClasses = (color, isActive) => {
        const colors = {
            blue: isActive
                ? 'bg-blue-600 text-white'
                : 'bg-blue-50 text-blue-600 hover:bg-blue-100',
            green: isActive
                ? 'bg-green-600 text-white'
                : 'bg-green-50 text-green-600 hover:bg-green-100',
            purple: isActive
                ? 'bg-purple-600 text-white'
                : 'bg-purple-50 text-purple-600 hover:bg-purple-100',
            indigo: isActive
                ? 'bg-indigo-600 text-white'
                : 'bg-indigo-50 text-indigo-600 hover:bg-indigo-100'
        };
        return colors[color];
    };

    const getIconColorClass = (color, isActive) => {
        if (isActive) {
            return 'text-white';
        }
        
        const iconColors = {
            blue: 'text-blue-600',
            green: 'text-green-600',
            purple: 'text-purple-600',
            indigo: 'text-indigo-600'
        };
        return iconColors[color];
    };

    return (
        <div className="bg-gray-50 py-8">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="text-center mb-8">
                    <h3 className="text-lg font-semibold text-gray-900">Explore Our Other Services</h3>
                </div>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    {services.map((service) => {
                        const isActive = url === service.href;
                        return (
                            <Link
                                key={service.name}
                                href={service.href}
                                className={`p-4 rounded-lg text-center transition-colors ${getColorClasses(service.color, isActive)} ${
                                    isActive ? 'cursor-default' : ''
                                }`}
                            >
                                <div className="text-2xl mb-2 flex justify-center items-center">
                                    <service.icon className={getIconColorClass(service.color, isActive)} />
                                </div>
                                <div className="font-semibold text-sm">{service.name}</div>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
