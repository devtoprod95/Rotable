export default function RadioField({ label, value, onChange, options = [], error }) {
    return (
        <div className="flex flex-col gap-1.5">
            <span className="text-sm font-semibold text-gray-700">{label}</span>
            <div className="flex gap-2">
                {options.map((opt) => {
                    const selected = String(value) === String(opt.id);

                    return (
                        <label
                            key={opt.id}
                            className={
                                'relative flex flex-1 cursor-pointer items-center justify-center rounded-lg border px-3.5 py-3 text-base transition ' +
                                (selected
                                    ? 'border-primary bg-primary-50 font-semibold text-primary'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400')
                            }
                        >
                            <input
                                type="radio"
                                className="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                checked={selected}
                                onChange={() => onChange(opt.id)}
                            />
                            {opt.label}
                        </label>
                    );
                })}
            </div>
            {error && <span className="text-xs text-red-500">{error}</span>}
        </div>
    );
}
