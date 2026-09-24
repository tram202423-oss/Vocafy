<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class QueuedResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    /**
     * Get the reset password notification mail message for the given URL.
     *
     * @param  string  $url
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function buildMailMessage($url)
    {
        $appName = config('app.name', 'Vocafy');
        $expireMinutes = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->subject('[' . $appName . '] Yêu cầu đặt lại mật khẩu của bạn')
            ->greeting('Xin chào!')
            ->line('Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản ' . $appName . ' liên kết với email này.')
            ->action('Đặt lại mật khẩu', $url)
            ->line('Liên kết đặt lại mật khẩu này có hiệu lực trong vòng ' . $expireMinutes . ' phút.')
            ->line('Nếu bạn không gửi yêu cầu này, bạn hoàn toàn có thể yên tâm bỏ qua email này. Mật khẩu của bạn vẫn được bảo mật an toàn.')
            ->line('Nếu bạn gặp bất kỳ vấn đề nào, vui lòng liên hệ Bộ phận Hỗ trợ khách hàng của chúng tôi tại support.vocafy@emptydev.io.vn.')
            ->salutation("Trân trọng,\nĐội ngũ Hỗ trợ " . $appName);
    }
}
