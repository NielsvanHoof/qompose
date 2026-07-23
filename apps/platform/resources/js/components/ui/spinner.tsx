import { Loader2Icon } from "lucide-react"

import { useTranslation } from "@/hooks/use-translation"
import { cn } from "@/lib/utils"

function Spinner({ className, ...props }: React.ComponentProps<"svg">) {
  const { t } = useTranslation()

  return (
    <Loader2Icon
      role="status"
      aria-label={t("Loading")}
      className={cn("size-4 animate-spin motion-reduce:animate-none", className)}
      {...props}
    />
  )
}

export { Spinner }
