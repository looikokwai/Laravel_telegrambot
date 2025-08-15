import React, { useState, useRef, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { FaGlobe, FaChevronDown } from 'react-icons/fa';

const LanguageSwitcher = () => {
    const { i18n, t } = useTranslation();
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef(null);

    const languages = [
        { code: 'en', name: 'English', flag: '🇺🇸' },
        { code: 'zh', name: '中文', flag: '🇨🇳' },
        { code: 'ms', name: 'Bahasa Melayu', flag: '🇲🇾' }
    ];

    const currentLanguage = languages.find(lang => lang.code === i18n.language) || languages[0];

    const handleLanguageChange = (languageCode) => {
        i18n.changeLanguage(languageCode);
        // 保存到 localStorage
        localStorage.setItem('i18nextLng', languageCode);
        setIsOpen(false); // 关闭下拉菜单
    };

    // 点击外部关闭下拉菜单
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };

        // 同时监听鼠标和触摸事件
        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('touchstart', handleClickOutside);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('touchstart', handleClickOutside);
        };
    }, []);

    return (
        <div className="relative" ref={dropdownRef}>
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors touch-manipulation"
                style={{ minHeight: '44px' }} // 移动端触摸友好的最小高度
            >
                <FaGlobe className="w-4 h-4" />
                <span className="flex items-center space-x-1">
                    {/* <span>{currentLanguage.flag}</span> */}
                    <span className="hidden sm:inline">{currentLanguage.name}</span>
                </span>
                <FaChevronDown className={`w-3 h-3 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
            </button>

            <div className={`absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg transition-all duration-200 z-50 border border-gray-200 ${
                isOpen
                    ? 'opacity-100 visible transform translate-y-0'
                    : 'opacity-0 invisible transform -translate-y-2'
            }`}>
                <div className="py-1">
                    {languages.map((language) => (
                        <button
                            key={language.code}
                            onClick={() => handleLanguageChange(language.code)}
                            className={`${
                                i18n.language === language.code
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-gray-700 hover:bg-gray-50'
                            } flex items-center w-full px-4 py-3 text-sm space-x-3 transition-colors touch-manipulation`}
                            style={{ minHeight: '44px' }} // 移动端触摸友好的最小高度
                        >
                            {/* <span className="text-lg">{language.flag}</span> */}
                            <span>{language.name}</span>
                            {i18n.language === language.code && (
                                <span className="ml-auto text-blue-600">✓</span>
                            )}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
};

export default LanguageSwitcher;
