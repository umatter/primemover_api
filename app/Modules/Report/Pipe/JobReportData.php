<?php

namespace App\Modules\Report\Pipe;

use App\Modules\Job\Model\Job;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use ZipArchive;

class JobReportData extends Data
{
    private ?ZipArchive $zipArchive = null;

    private ?string $zipPath = null;

    private ?Job $job = null;

    private ?Collection $reports = null;

    public function getZipArchive(): ?ZipArchive
    {
        return $this->zipArchive;
    }

    public function setZipArchive(?ZipArchive $zipArchive): JobReportData
    {
        $this->zipArchive = $zipArchive;

        return $this;
    }

    public function getZipPath(): ?string
    {
        return $this->zipPath;
    }

    public function setZipPath(?string $zipPath): JobReportData
    {
        $this->zipPath = $zipPath;

        return $this;
    }

    public function getJob(): ?Job
    {
        return $this->job;
    }

    public function setJob(?Job $job): JobReportData
    {
        $this->job = $job;

        return $this;
    }

    public function getReports(): ?Collection
    {
        return $this->reports;
    }

    public function setReports(?Collection $reports): JobReportData
    {
        $this->reports = $reports;

        return $this;
    }
}
