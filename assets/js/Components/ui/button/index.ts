import { cva, type VariantProps } from 'class-variance-authority'

export { default as Button } from './Button.vue'

export const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl text-sm font-semibold transition-all duration-200 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*=\'size-\'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none active:scale-[0.98]',
  {
    variants: {
      variant: {
        default:
          'bg-gradient-to-b from-[#4e6bf5] to-[#3652de] border border-[#304bc9]/80 !text-white shadow-[0_4px_14px_0_rgba(67,97,238,0.39),inset_0_1px_0_rgba(255,255,255,0.25)] hover:shadow-[0_6px_20px_rgba(67,97,238,0.48),inset_0_1px_0_rgba(255,255,255,0.35)] hover:-translate-y-0.5 active:translate-y-0 active:shadow-inner cursor-pointer',
        destructive:
          'bg-gradient-to-b from-rose-500 to-red-600 border border-red-700/80 !text-white shadow-[0_4px_14px_rgba(225,29,72,0.32),inset_0_1px_0_rgba(255,255,255,0.25)] hover:shadow-[0_6px_20px_rgba(225,29,72,0.45)] hover:-translate-y-0.5 active:translate-y-0 active:shadow-inner cursor-pointer',
        outline:
          'border border-slate-200 dark:border-slate-700 bg-white/95 dark:bg-slate-800/90 text-slate-800 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700/80 shadow-xs hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:shadow-inner backdrop-blur-xs cursor-pointer',
        secondary:
          'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700/60 shadow-xs hover:-translate-y-0.5 active:translate-y-0 cursor-pointer',
        ghost:
          'hover:bg-slate-100/80 dark:hover:bg-slate-800/80 text-slate-700 dark:text-slate-300 shadow-none hover:-translate-y-0.5 cursor-pointer',
        link: 'text-primary underline-offset-4 hover:underline shadow-none cursor-pointer',
      },
      size: {
        default: 'h-9 px-4 py-2 text-sm',
        sm: 'h-8 rounded-lg gap-1.5 px-3 text-xs',
        lg: 'h-11 rounded-xl px-7 text-base font-bold',
        icon: 'size-9 rounded-xl',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
)

export type ButtonVariants = VariantProps<typeof buttonVariants>
